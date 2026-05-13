import { useCallback, useEffect, useMemo, useState } from "react";

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL || "";
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || "";
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "/api";
const roles = [
  { id: "student", label: "Student", helper: "Use your admission number or email" },
  { id: "lecturer", label: "Lecturer", helper: "Enter marks and view class progress" },
  { id: "admin", label: "Admin", helper: "Manage the whole school view" },
];

function App() {
  const [supabaseClient, setSupabaseClient] = useState(null);
  const [activeRole, setActiveRole] = useState("student");
  const [authMode, setAuthMode] = useState("login");
  const [user, setUser] = useState(null);
  const [message, setMessage] = useState("");
  const [loginForm, setLoginForm] = useState({ identifier: "", password: "" });
  const [registerForm, setRegisterForm] = useState({ name: "", email: "", admissionNumber: "", password: "" });
  const [resetForm, setResetForm] = useState({ identifier: "" });
  const [students, setStudents] = useState([]);
  const [marks, setMarks] = useState([]);
  const [markForm, setMarkForm] = useState({ studentId: "", subject: "", marks: "" });

  const selectedRole = roles.find((role) => role.id === activeRole);
  const canManageMarks = useMemo(() => ["admin", "lecturer"].includes(user?.role), [user?.role]);
  const averageMark = useMemo(() => averageMarks(marks), [marks]);
  const trackedCount = canManageMarks ? students.length : marks.length;

  const showError = useCallback((error, fallbackMessage) => {
    setMessage(error?.message || fallbackMessage);
  }, []);

  useEffect(() => {
    if (!supabaseUrl || !supabaseAnonKey || supabaseClient) {
      return undefined;
    }

    const connect = () => {
      if (window.supabase?.createClient) {
        setSupabaseClient(window.supabase.createClient(supabaseUrl, supabaseAnonKey));
      }
    };

    connect();
    const timer = window.setInterval(connect, 250);
    return () => window.clearInterval(timer);
  }, [supabaseClient]);

  const loadMarks = useCallback(async (profile) => {
    if (!profile) return;

    const data = await apiRequest("marks.php");
    setMarks((data.marks || []).map(normalizeMarkRecord));
  }, []);

  const loadStudents = useCallback(async (profile) => {
    if (!["admin", "lecturer"].includes(profile?.role)) {
      setStudents([]);
      return;
    }

    const data = await apiRequest("marks.php?students=1");
    setStudents(data.students || []);
  }, []);

  const ensureProfile = useCallback(async (authUser, fallbackRole = "student") => {
    const { data, error } = await supabaseClient
      .from("profiles")
      .select("id, name, email, admission_number, role")
      .eq("id", authUser.id)
      .maybeSingle();

    if (error) throw error;
    if (data) return data;

    const requestedRole = localStorage.getItem("pendingRole") || fallbackRole;
    if (requestedRole !== "student") {
      throw new Error("This account must be added by an admin before using this role.");
    }

    const profile = {
      id: authUser.id,
      name: authUser.user_metadata?.full_name || authUser.email,
      email: authUser.email,
      role: "student",
    };

    const { data: createdProfile, error: createError } = await supabaseClient
      .from("profiles")
      .insert(profile)
      .select("id, name, email, admission_number, role")
      .single();

    if (createError) throw createError;
    return createdProfile;
  }, [supabaseClient]);

  const loadSchoolData = useCallback(async (authUser, fallbackRole = activeRole) => {
    if (!authUser) return;

    try {
      const profile = authUser.role ? authUser : await ensureProfile(authUser, fallbackRole);
      localStorage.removeItem("pendingRole");
      setUser(profile);
      setActiveRole(profile.role || "student");
      await Promise.all([loadMarks(profile), loadStudents(profile)]);
      setMessage("");
    } catch (error) {
      showError(error, "Failed to load your school profile.");
      if (supabaseClient) {
        await supabaseClient.auth.signOut();
      }
      setUser(null);
    }
  }, [activeRole, ensureProfile, loadMarks, loadStudents, showError, supabaseClient]);

  useEffect(() => {
    const token = localStorage.getItem("token");
    if (!token || user) return;

    apiRequest("me.php")
      .then((data) => loadSchoolData(data.user))
      .catch(() => localStorage.removeItem("token"));
  }, [loadSchoolData, user]);

  useEffect(() => {
    if (!supabaseClient) return undefined;

    let active = true;
    supabaseClient.auth.getSession().then(({ data }) => {
      if (active && data.session?.user) {
        void loadSchoolData(data.session.user);
      }
    });

    const { data: listener } = supabaseClient.auth.onAuthStateChange((event, session) => {
      if (event === "SIGNED_IN" && session?.user) {
        void loadSchoolData(session.user);
      }
      if (event === "SIGNED_OUT") {
        setUser(null);
        setStudents([]);
        setMarks([]);
      }
    });

    return () => {
      active = false;
      listener.subscription.unsubscribe();
    };
  }, [loadSchoolData, supabaseClient]);

  async function resolveEmail(identifier, role) {
    const { data, error } = await supabaseClient.rpc("resolve_login_identifier", {
      login_identifier: identifier,
      selected_role: role,
    });

    if (error) throw error;
    if (!data) throw new Error("No matching account found for that role.");
    return data;
  }

  async function handleLogin(event) {
    event.preventDefault();

    try {
      const data = await apiRequest("Login.php", {
        method: "POST",
        body: {
          identifier: loginForm.identifier,
          role: activeRole,
          password: loginForm.password,
        },
      });

      localStorage.setItem("token", data.token);
      setLoginForm({ identifier: "", password: "" });
      await loadSchoolData(data.user, activeRole);
    } catch (error) {
      showError(error, "Login failed.");
    }
  }

  async function handleRegister(event) {
    event.preventDefault();

    try {
      const data = await apiRequest("register.php", {
        method: "POST",
        body: {
          name: registerForm.name,
          email: registerForm.email,
          admissionNumber: registerForm.admissionNumber,
          role: activeRole,
          password: registerForm.password,
        },
      });

      localStorage.setItem("token", data.token);
      setRegisterForm({ name: "", email: "", admissionNumber: "", password: "" });
      setLoginForm({ identifier: data.user.email, password: "" });
      await loadSchoolData(data.user, activeRole);
    } catch (error) {
      showError(error, "Account creation failed.");
    }
  }

  async function handleReset(event) {
    event.preventDefault();
    if (!supabaseClient) {
      setMessage("Add Supabase URL and anon key to enable password reset.");
      return;
    }

    try {
      const email = await resolveEmail(resetForm.identifier, activeRole);
      const { error } = await supabaseClient.auth.resetPasswordForEmail(email, {
        redirectTo: window.location.origin,
      });

      if (error) throw error;
      setAuthMode("login");
      setResetForm({ identifier: "" });
      setMessage("Password reset email sent. Check your inbox.");
    } catch (error) {
      showError(error, "Password reset failed.");
    }
  }

  async function handleGoogleLogin() {
    if (!supabaseClient) {
      setMessage("Add Supabase URL and anon key to enable Google login.");
      return;
    }

    localStorage.setItem("pendingRole", activeRole);
    const { error } = await supabaseClient.auth.signInWithOAuth({
      provider: "google",
      options: { redirectTo: window.location.origin },
    });

    if (error) showError(error, "Google login failed.");
  }

  async function logout() {
    if (supabaseClient) {
      await supabaseClient.auth.signOut();
    }
    localStorage.removeItem("pendingRole");
    localStorage.removeItem("token");
    setUser(null);
    setStudents([]);
    setMarks([]);
    setMessage("");
  }

  async function saveMarks(event) {
    event.preventDefault();
    try {
      const data = await apiRequest("marks.php", {
        method: "POST",
        body: {
          studentId: markForm.studentId,
          subject: markForm.subject,
          marks: Number(markForm.marks),
        },
      });

      setMarkForm({ studentId: "", subject: "", marks: "" });
      await loadMarks(user);
      setMessage(`Marks saved. Grade: ${data.grade} (${data.remarks}).`);
    } catch (error) {
      showError(error, "Failed to save marks.");
    }
  }

  if (!user) {
    return (
      <main className="auth-page">
        <section className="brand-panel">
          <div className="brand-content">
            <div className="brand-mark">
              <img src="/Logo.png" alt="Elimu School" />
              <span>Elimu School</span>
            </div>
            <h1>School management made clear and dependable.</h1>
            <p>{selectedRole.helper}</p>
            <div className="brand-stats" aria-label="School platform highlights">
              <span>
                <strong>3</strong>
                Portals
              </span>
              <span>
                <strong>24/7</strong>
                Access
              </span>
              <span>
                <strong>100%</strong>
                Secure
              </span>
            </div>
          </div>
        </section>

        <section className="auth-panel">
          <div className="auth-heading">
            <p className="eyebrow">Welcome back</p>
            <h2>{authMode === "login" ? `${selectedRole.label} Login` : authMode === "register" ? `Create ${selectedRole.label} Account` : `Reset ${selectedRole.label} Password`}</h2>
            <p>{selectedRole.helper}</p>
          </div>

          <div className="role-tabs" aria-label="Login role">
            {roles.map((role) => (
              <button
                key={role.id}
                type="button"
                className={activeRole === role.id ? "active" : ""}
                onClick={() => setActiveRole(role.id)}
              >
                <span>{role.label}</span>
              </button>
            ))}
          </div>

          <div className="mode-tabs">
            <button type="button" className={authMode === "login" ? "active" : ""} onClick={() => setAuthMode("login")}>
              Login
            </button>
            <button type="button" className={authMode === "register" ? "active" : ""} onClick={() => setAuthMode("register")}>
              Create Account
            </button>
            <button type="button" className={authMode === "reset" ? "active" : ""} onClick={() => setAuthMode("reset")}>
              Reset Password
            </button>
          </div>

          {authMode === "login" ? (
            <form onSubmit={handleLogin} className="stack">
              <label htmlFor="identifier">{activeRole === "student" ? "Admission number or email" : "Email"}</label>
              <input
                id="identifier"
                value={loginForm.identifier}
                onChange={(event) => setLoginForm({ ...loginForm, identifier: event.target.value })}
                placeholder={activeRole === "student" ? "20/194" : `${activeRole}@school.local`}
                required
              />
              <label htmlFor="password">Password</label>
              <input
                id="password"
                type="password"
                value={loginForm.password}
                onChange={(event) => setLoginForm({ ...loginForm, password: event.target.value })}
                required
              />
              <button type="submit">Sign In</button>
            </form>
          ) : authMode === "register" ? (
            <form onSubmit={handleRegister} className="stack">
              <label htmlFor="registerName">Full name</label>
              <input
                id="registerName"
                value={registerForm.name}
                onChange={(event) => setRegisterForm({ ...registerForm, name: event.target.value })}
                placeholder="Jane Student"
                required
              />
              <label htmlFor="registerEmail">Email</label>
              <input
                id="registerEmail"
                type="email"
                value={registerForm.email}
                onChange={(event) => setRegisterForm({ ...registerForm, email: event.target.value })}
                placeholder="jane@student.local"
                required
              />
              {activeRole === "student" && (
                <>
                  <label htmlFor="registerAdmission">Admission number</label>
                  <input
                    id="registerAdmission"
                    value={registerForm.admissionNumber}
                    onChange={(event) => setRegisterForm({ ...registerForm, admissionNumber: event.target.value })}
                    placeholder="20/194"
                  />
                </>
              )}
              <label htmlFor="registerPassword">Password</label>
              <input
                id="registerPassword"
                type="password"
                value={registerForm.password}
                onChange={(event) => setRegisterForm({ ...registerForm, password: event.target.value })}
                minLength={6}
                required
              />
              <button type="submit">Create Account</button>
            </form>
          ) : (
            <form onSubmit={handleReset} className="stack">
              <label htmlFor="resetIdentifier">{activeRole === "student" ? "Admission number or email" : "Email"}</label>
              <input
                id="resetIdentifier"
                value={resetForm.identifier}
                onChange={(event) => setResetForm({ identifier: event.target.value })}
                required
              />
              <button type="submit">Send Reset Link</button>
            </form>
          )}

          {authMode === "login" && supabaseClient && (
            <div className="google-login">
              <div className="divider"><span>or</span></div>
              <button type="button" className="google-button native-google" onClick={handleGoogleLogin}>
                Continue with Google
              </button>
            </div>
          )}

          {message && <p className="notice">{message}</p>}
        </section>
      </main>
    );
  }

  return (
    <main className="dashboard">
      <header className="topbar">
        <div className="topbar-copy">
          <div className="dashboard-brand">
            <img src="/Logo.png" alt="Elimu School" />
            <span>Elimu School</span>
          </div>
          <p className="eyebrow">{user.role}</p>
          <h1>{dashboardTitle(user.role)}</h1>
          <div className="user-meta">
            <span>{user.name}</span>
            <span>{user.email}</span>
            {user.admission_number && <span>Adm: {user.admission_number}</span>}
          </div>
        </div>
        <button type="button" className="secondary-action" onClick={logout}>Logout</button>
      </header>

      {message && <p className="notice">{message}</p>}

      <section className="summary-grid">
        <article>
          <span className="card-label">{canManageMarks ? "Learners" : "Progress"}</span>
          <strong>{trackedCount}</strong>
          <span>{canManageMarks ? "Students tracked" : "Subjects graded"}</span>
        </article>
        <article>
          <span className="card-label">Records</span>
          <strong>{marks.length}</strong>
          <span>Total mark records</span>
        </article>
        <article>
          <span className="card-label">Performance</span>
          <strong>{averageMark}</strong>
          <span>Average marks</span>
        </article>
      </section>

      {canManageMarks && (
        <section className="panel two-column">
          <div className="form-panel">
            <div className="panel-heading">
              <p className="eyebrow">Assessment</p>
              <h2>Enter Marks</h2>
            </div>
            <form onSubmit={saveMarks} className="stack">
              <label htmlFor="studentId">Student</label>
              <select
                id="studentId"
                value={markForm.studentId}
                onChange={(event) => setMarkForm({ ...markForm, studentId: event.target.value })}
                required
              >
                <option value="">Select student</option>
                {students.map((student) => (
                  <option key={student.id} value={student.id}>
                    {student.name} {student.admission_number ? `(${student.admission_number})` : ""}
                  </option>
                ))}
              </select>
              <label htmlFor="subject">Subject</label>
              <input
                id="subject"
                value={markForm.subject}
                onChange={(event) => setMarkForm({ ...markForm, subject: event.target.value })}
                placeholder="Mathematics"
                required
              />
              <label htmlFor="marks">Marks</label>
              <input
                id="marks"
                type="number"
                min="0"
                max="100"
                value={markForm.marks}
                onChange={(event) => setMarkForm({ ...markForm, marks: event.target.value })}
                required
              />
              <button type="submit">Save Grade</button>
            </form>
          </div>

          <aside className="guidance-card">
            <p className="eyebrow">{user.role === "admin" ? "Admin View" : "Lecturer View"}</p>
            <h2>{user.role === "admin" ? "Whole-school oversight" : "Class progress"}</h2>
            <p className="muted">
              {user.role === "admin"
                ? "Admins can review all student marks and overall performance."
                : "Lecturers can enter marks; grades are calculated automatically."}
            </p>
            <div className="guidance-list">
              <span>Grades update after each saved mark.</span>
              <span>Tables stay readable across smaller screens.</span>
              <span>Performance summaries remain visible at the top.</span>
            </div>
          </aside>
        </section>
      )}

      <section className="panel">
        <div className="panel-heading table-heading">
          <div>
            <p className="eyebrow">Gradebook</p>
            <h2>{canManageMarks ? "All Student Grades" : "My Grades"}</h2>
          </div>
          <span className="record-count">{marks.length} records</span>
        </div>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                {canManageMarks && <th>Student</th>}
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Remarks</th>
                <th>Lecturer</th>
              </tr>
            </thead>
            <tbody>
              {marks.map((record) => (
                <tr key={record.id}>
                  {canManageMarks && (
                    <td>
                      {record.student_name}
                      <span>{record.admission_number || "No admission number"}</span>
                    </td>
                  )}
                  <td>{record.subject}</td>
                  <td>{Number(record.marks).toFixed(0)}</td>
                  <td><strong>{record.grade}</strong></td>
                  <td>{record.remarks}</td>
                  <td>{record.lecturer_name}</td>
                </tr>
              ))}
              {marks.length === 0 && (
                <tr>
                  <td className="empty-state" colSpan={canManageMarks ? 6 : 5}>
                    No marks recorded yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </section>
    </main>
  );
}

function normalizeMarkRecord(record) {
  return {
    id: record.id,
    subject: record.subject,
    marks: record.marks,
    grade: record.grade,
    remarks: record.remarks,
    student_name: record.student?.name || record.student_name,
    admission_number: record.student?.admission_number || record.admission_number,
    lecturer_name: record.lecturer?.name || record.lecturer_name || "School staff",
  };
}

async function apiRequest(path, options = {}) {
  const token = localStorage.getItem("token");
  const headers = {
    Accept: "application/json",
    ...(options.body ? { "Content-Type": "application/json" } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  const response = await fetch(`${API_BASE_URL}/${path}`, {
    method: options.method || "GET",
    headers,
    body: options.body ? JSON.stringify(options.body) : undefined,
  });
  const data = await response.json().catch(() => ({}));

  if (!response.ok || data.status === "error") {
    throw new Error(data.message || "Request failed.");
  }

  return data;
}

function dashboardTitle(role) {
  if (role === "admin") return "Administration Dashboard";
  if (role === "lecturer") return "Lecturer Marks Dashboard";
  return "Student Results Dashboard";
}

function averageMarks(records) {
  if (!records.length) return "0";
  const total = records.reduce((sum, record) => sum + Number(record.marks || 0), 0);
  return (total / records.length).toFixed(1);
}

export default App;
