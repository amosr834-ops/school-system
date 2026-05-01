import { useCallback, useEffect, useMemo, useState } from "react";

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL || "";
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || "";
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
    if (!supabaseClient || !profile) return;

    const selectFields = `
      id,
      subject,
      marks,
      grade,
      remarks,
      updated_at,
      student:profiles!student_marks_student_id_fkey(id, name, admission_number),
      lecturer:profiles!student_marks_lecturer_id_fkey(name)
    `;
    let query = supabaseClient.from("student_marks").select(selectFields).order("subject", { ascending: true });

    if (profile.role === "student") {
      query = query.eq("student_id", profile.id);
    }

    const { data, error } = await query;
    if (error) throw error;

    setMarks((data || []).map(normalizeMarkRecord));
  }, [supabaseClient]);

  const loadStudents = useCallback(async (profile) => {
    if (!supabaseClient || !["admin", "lecturer"].includes(profile?.role)) {
      setStudents([]);
      return;
    }

    const { data, error } = await supabaseClient
      .from("profiles")
      .select("id, name, email, admission_number")
      .eq("role", "student")
      .order("name", { ascending: true });

    if (error) throw error;
    setStudents(data || []);
  }, [supabaseClient]);

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
    if (!supabaseClient || !authUser) return;

    try {
      const profile = await ensureProfile(authUser, fallbackRole);
      localStorage.removeItem("pendingRole");
      setUser(profile);
      setActiveRole(profile.role || "student");
      await Promise.all([loadMarks(profile), loadStudents(profile)]);
      setMessage("");
    } catch (error) {
      showError(error, "Failed to load your school profile.");
      await supabaseClient.auth.signOut();
      setUser(null);
    }
  }, [activeRole, ensureProfile, loadMarks, loadStudents, showError, supabaseClient]);

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
    if (!supabaseClient) {
      setMessage("Add Supabase URL and anon key to enable login.");
      return;
    }

    try {
      const email = await resolveEmail(loginForm.identifier, activeRole);
      localStorage.setItem("pendingRole", activeRole);
      const { data, error } = await supabaseClient.auth.signInWithPassword({
        email,
        password: loginForm.password,
      });

      if (error) throw error;
      setLoginForm({ identifier: "", password: "" });
      await loadSchoolData(data.user, activeRole);
    } catch (error) {
      showError(error, "Login failed.");
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
    setUser(null);
    setStudents([]);
    setMarks([]);
    setMessage("");
  }

  async function saveMarks(event) {
    event.preventDefault();
    try {
      const { data, error } = await supabaseClient
        .from("student_marks")
        .upsert(
          {
            student_id: markForm.studentId,
            lecturer_id: user.id,
            subject: markForm.subject,
            marks: Number(markForm.marks),
          },
          { onConflict: "student_id,subject" }
        )
        .select("grade, remarks")
        .single();

      if (error) throw error;
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
          <img src="/Logo.png" alt="Elimu School" />
          <h1>Elimu School Management System</h1>
          <p>{selectedRole.helper}</p>
        </section>

        <section className="auth-panel">
          <div className="role-tabs" aria-label="Login role">
            {roles.map((role) => (
              <button
                key={role.id}
                type="button"
                className={activeRole === role.id ? "active" : ""}
                onClick={() => setActiveRole(role.id)}
              >
                {role.label}
              </button>
            ))}
          </div>

          <div className="mode-tabs">
            <button type="button" className={authMode === "login" ? "active" : ""} onClick={() => setAuthMode("login")}>
              Login
            </button>
            <button type="button" className={authMode === "reset" ? "active" : ""} onClick={() => setAuthMode("reset")}>
              Reset Password
            </button>
          </div>

          {authMode === "login" ? (
            <form onSubmit={handleLogin} className="stack">
              <h2>{selectedRole.label} Login</h2>
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
          ) : (
            <form onSubmit={handleReset} className="stack">
              <h2>Reset {selectedRole.label} Password</h2>
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

          {authMode === "login" && (
            <div className="google-login">
              <div className="divider"><span>or</span></div>
              <button type="button" className="google-button native-google" onClick={handleGoogleLogin}>
                Continue with Google
              </button>
            </div>
          )}

          {(!supabaseUrl || !supabaseAnonKey) && (
            <p className="notice">Add Supabase environment variables to connect this deployment.</p>
          )}
          {message && <p className="notice">{message}</p>}
        </section>
      </main>
    );
  }

  return (
    <main className="dashboard">
      <header className="topbar">
        <div>
          <p className="eyebrow">{user.role}</p>
          <h1>{dashboardTitle(user.role)}</h1>
          <p>
            {user.name} | {user.email}
            {user.admission_number ? ` | Adm: ${user.admission_number}` : ""}
          </p>
        </div>
        <button type="button" onClick={logout}>Logout</button>
      </header>

      {message && <p className="notice">{message}</p>}

      <section className="summary-grid">
        <article>
          <strong>{trackedCount}</strong>
          <span>{canManageMarks ? "Students tracked" : "Subjects graded"}</span>
        </article>
        <article>
          <strong>{marks.length}</strong>
          <span>Total mark records</span>
        </article>
        <article>
          <strong>{averageMark}</strong>
          <span>Average marks</span>
        </article>
      </section>

      {canManageMarks && (
        <section className="panel two-column">
          <div>
            <h2>Enter Marks</h2>
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

          <div>
            <h2>{user.role === "admin" ? "Admin View" : "Lecturer View"}</h2>
            <p className="muted">
              {user.role === "admin"
                ? "Admins can review all student marks and overall performance."
                : "Lecturers can enter marks; grades are calculated automatically."}
            </p>
          </div>
        </section>
      )}

      <section className="panel">
        <h2>{canManageMarks ? "All Student Grades" : "My Grades"}</h2>
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
                  <td colSpan={canManageMarks ? 6 : 5}>No marks recorded yet.</td>
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
    student_name: record.student?.name,
    admission_number: record.student?.admission_number,
    lecturer_name: record.lecturer?.name || "School staff",
  };
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
