import { useCallback, useEffect, useMemo, useState } from "react";
import axios from "axios";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "/api";

const api = axios.create({
  baseURL: apiBaseUrl,
  headers: {
    "Content-Type": "application/json",
  },
});

function App() {
  const [mode, setMode] = useState("login");
  const [token, setToken] = useState(localStorage.getItem("token") || "");
  const [user, setUser] = useState(null);
  const [teams, setTeams] = useState([]);
  const [tasks, setTasks] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [comments, setComments] = useState([]);
  const [selectedTaskId, setSelectedTaskId] = useState(null);
  const [message, setMessage] = useState("");

  const [authForm, setAuthForm] = useState({ name: "", email: "", password: "" });
  const [teamName, setTeamName] = useState("");
  const [memberForm, setMemberForm] = useState({ teamId: "", email: "" });
  const [taskForm, setTaskForm] = useState({
    title: "",
    description: "",
    teamId: "",
    priority: "Medium",
    dueDate: "",
    assigneeEmail: "",
  });
  const [commentBody, setCommentBody] = useState("");
  const authBackgroundStyle = {
    backgroundImage: 'linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url("/background.png")',
    backgroundSize: "cover",
    backgroundPosition: "center",
    backgroundRepeat: "no-repeat",
  };

  const authHeaders = useMemo(
    () => ({
      Authorization: `Bearer ${token}`,
    }),
    [token]
  );

  const handleApiError = useCallback((error, fallbackMessage) => {
    const apiMessage = error?.response?.data?.message;
    setMessage(apiMessage || fallbackMessage);
  }, []);

  const logout = useCallback(() => {
    localStorage.removeItem("token");
    setToken("");
    setTeams([]);
    setTasks([]);
    setNotifications([]);
    setComments([]);
    setSelectedTaskId(null);
  }, []);

  const loadDashboard = useCallback(async () => {
    try {
      const [meRes, teamsRes, tasksRes, notificationsRes] = await Promise.all([
        api.get("/me.php", { headers: authHeaders }),
        api.get("/teams.php", { headers: authHeaders }),
        api.get("/tasks.php", { headers: authHeaders }),
        api.get("/notifications.php", { headers: authHeaders }),
      ]);

      setUser(meRes.data.user);
      setTeams(teamsRes.data.teams || []);
      setTasks(tasksRes.data.tasks || []);
      setNotifications(notificationsRes.data.notifications || []);
      setMessage("");
    } catch (error) {
      handleApiError(error, "Session expired. Please log in again.");
      logout();
    }
  }, [authHeaders, handleApiError, logout]);

  const loadComments = useCallback(async (taskId) => {
    try {
      const response = await api.get(`/comments.php?taskId=${taskId}`, { headers: authHeaders });
      setComments(response.data.comments || []);
    } catch (error) {
      handleApiError(error, "Failed to load comments.");
    }
  }, [authHeaders, handleApiError]);

  useEffect(() => {
    if (!token) {
      return;
    }
    void loadDashboard();
  }, [token, loadDashboard]);

  useEffect(() => {
    if (!selectedTaskId || !token) {
      return;
    }
    void loadComments(selectedTaskId);
  }, [selectedTaskId, token, loadComments]);

  async function handleAuthSubmit(event) {
    event.preventDefault();
    try {
      const endpoint = mode === "register" ? "/register.php" : "/Login.php";
      const payload =
        mode === "register"
          ? authForm
          : { email: authForm.email, password: authForm.password };

      const response = await api.post(endpoint, payload);
      const newToken = response.data.token;
      localStorage.setItem("token", newToken);
      setToken(newToken);
      setAuthForm({ name: "", email: "", password: "" });
      setMessage("");
    } catch (error) {
      handleApiError(error, "Authentication failed.");
    }
  }

  async function createTeam(event) {
    event.preventDefault();
    try {
      await api.post(
        "/teams.php",
        { action: "create", name: teamName },
        { headers: authHeaders }
      );
      setTeamName("");
      await loadDashboard();
      setMessage("Team created.");
    } catch (error) {
      handleApiError(error, "Failed to create team.");
    }
  }

  async function addMember(event) {
    event.preventDefault();
    try {
      await api.post(
        "/teams.php",
        {
          action: "add_member",
          teamId: Number(memberForm.teamId),
          email: memberForm.email,
        },
        { headers: authHeaders }
      );
      setMemberForm({ teamId: "", email: "" });
      setMessage("Member added.");
    } catch (error) {
      handleApiError(error, "Failed to add member.");
    }
  }

  async function createTask(event) {
    event.preventDefault();
    try {
      await api.post(
        "/tasks.php",
        {
          ...taskForm,
          teamId: Number(taskForm.teamId),
        },
        { headers: authHeaders }
      );
      setTaskForm({
        title: "",
        description: "",
        teamId: "",
        priority: "Medium",
        dueDate: "",
        assigneeEmail: "",
      });
      await loadDashboard();
      setMessage("Task created.");
    } catch (error) {
      handleApiError(error, "Failed to create task.");
    }
  }

  async function updateTask(task) {
    try {
      await api.put(
        "/tasks.php",
        {
          taskId: Number(task.id),
          status: task.status,
          priority: task.priority,
          dueDate: task.due_date || "",
        },
        { headers: authHeaders }
      );
      setMessage("Task updated.");
      await loadDashboard();
    } catch (error) {
      handleApiError(error, "Failed to update task.");
    }
  }

  async function addComment(event) {
    event.preventDefault();
    if (!selectedTaskId) return;

    try {
      await api.post(
        "/comments.php",
        {
          taskId: selectedTaskId,
          body: commentBody,
        },
        { headers: authHeaders }
      );
      setCommentBody("");
      await loadComments(selectedTaskId);
      await loadDashboard();
    } catch (error) {
      handleApiError(error, "Failed to add comment.");
    }
  }

  async function markNotificationRead(notificationId) {
    try {
      await api.post(
        "/notifications.php",
        { notificationId },
        { headers: authHeaders }
      );
      await loadDashboard();
    } catch (error) {
      handleApiError(error, "Failed to update notification.");
    }
  }

  if (!token) {
    return (
      <main className="auth-page" style={authBackgroundStyle}>
        <section className="auth-card">
          <h1>Collaborative Task Management</h1>
          <p>Plan work, assign tasks, collaborate in teams, and track deadlines.</p>
          <div className="mode-toggle">
            <button
              className={mode === "login" ? "active" : ""}
              onClick={() => setMode("login")}
              type="button"
            >
              Login
            </button>
            <button
              className={mode === "register" ? "active" : ""}
              onClick={() => setMode("register")}
              type="button"
            >
              Register
            </button>
          </div>
          <form onSubmit={handleAuthSubmit}>
            {mode === "register" && (
              <input
                type="text"
                placeholder="Full name"
                value={authForm.name}
                onChange={(e) => setAuthForm({ ...authForm, name: e.target.value })}
                required
              />
            )}
            <input
              type="email"
              placeholder="Email"
              value={authForm.email}
              onChange={(e) => setAuthForm({ ...authForm, email: e.target.value })}
              required
            />
            <input
              type="password"
              placeholder="Password"
              value={authForm.password}
              onChange={(e) => setAuthForm({ ...authForm, password: e.target.value })}
              required
            />
            <button type="submit">{mode === "register" ? "Create Account" : "Sign In"}</button>
          </form>
          {message && <p className="notice">{message}</p>}
        </section>
      </main>
    );
  }

  return (
    <main className="dashboard">
      <header className="topbar">
        <div>
          <h1>Team Workspace</h1>
          <p>{user ? `${user.name} (${user.email})` : "Loading profile..."}</p>
        </div>
        <button onClick={logout} type="button">
          Logout
        </button>
      </header>

      {message && <p className="notice">{message}</p>}

      <section className="panel-grid">
        <article className="panel">
          <h2>Teams</h2>
          <form onSubmit={createTeam} className="stack">
            <input
              type="text"
              placeholder="New team name"
              value={teamName}
              onChange={(e) => setTeamName(e.target.value)}
              required
            />
            <button type="submit">Create Team</button>
          </form>
          <form onSubmit={addMember} className="stack compact">
            <select
              value={memberForm.teamId}
              onChange={(e) => setMemberForm({ ...memberForm, teamId: e.target.value })}
              required
            >
              <option value="">Select team</option>
              {teams.map((team) => (
                <option key={team.id} value={team.id}>
                  {team.name}
                </option>
              ))}
            </select>
            <input
              type="email"
              placeholder="Member email"
              value={memberForm.email}
              onChange={(e) => setMemberForm({ ...memberForm, email: e.target.value })}
              required
            />
            <button type="submit">Add Member</button>
          </form>
          <ul>
            {teams.map((team) => (
              <li key={team.id}>
                {team.name} {Number(team.owner_id) === Number(user?.id) ? "(Owner)" : ""}
              </li>
            ))}
          </ul>
        </article>

        <article className="panel">
          <h2>Create Task</h2>
          <form onSubmit={createTask} className="stack">
            <input
              type="text"
              placeholder="Task title"
              value={taskForm.title}
              onChange={(e) => setTaskForm({ ...taskForm, title: e.target.value })}
              required
            />
            <textarea
              placeholder="Task description"
              value={taskForm.description}
              onChange={(e) => setTaskForm({ ...taskForm, description: e.target.value })}
            />
            <select
              value={taskForm.teamId}
              onChange={(e) => setTaskForm({ ...taskForm, teamId: e.target.value })}
              required
            >
              <option value="">Select team</option>
              {teams.map((team) => (
                <option key={team.id} value={team.id}>
                  {team.name}
                </option>
              ))}
            </select>
            <select
              value={taskForm.priority}
              onChange={(e) => setTaskForm({ ...taskForm, priority: e.target.value })}
            >
              <option value="Low">Low Priority</option>
              <option value="Medium">Medium Priority</option>
              <option value="High">High Priority</option>
            </select>
            <input
              type="date"
              value={taskForm.dueDate}
              onChange={(e) => setTaskForm({ ...taskForm, dueDate: e.target.value })}
            />
            <input
              type="email"
              placeholder="Assignee email (optional)"
              value={taskForm.assigneeEmail}
              onChange={(e) => setTaskForm({ ...taskForm, assigneeEmail: e.target.value })}
            />
            <button type="submit">Create Task</button>
          </form>
        </article>

        <article className="panel">
          <h2>Notifications</h2>
          <ul className="notifications">
            {notifications.map((n) => (
              <li key={n.id}>
                <div>
                  <strong>{n.is_read ? "Read" : "Unread"}</strong>
                  <p>{n.message}</p>
                </div>
                {!Number(n.is_read) && (
                  <button type="button" onClick={() => markNotificationRead(n.id)}>
                    Mark Read
                  </button>
                )}
              </li>
            ))}
          </ul>
        </article>
      </section>

      <section className="panel">
        <h2>Tasks</h2>
        <div className="task-table-wrap">
          <table className="task-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Deadline</th>
                <th>Team</th>
                <th>Assignee</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {tasks.map((task) => (
                <tr key={task.id} className={Number(selectedTaskId) === Number(task.id) ? "active-row" : ""}>
                  <td>
                    <button type="button" className="linkish" onClick={() => setSelectedTaskId(task.id)}>
                      {task.title}
                    </button>
                  </td>
                  <td>
                    <select
                      value={task.priority}
                      onChange={(e) =>
                        setTasks((prev) =>
                          prev.map((t) => (t.id === task.id ? { ...t, priority: e.target.value } : t))
                        )
                      }
                    >
                      <option value="Low">Low</option>
                      <option value="Medium">Medium</option>
                      <option value="High">High</option>
                    </select>
                  </td>
                  <td>
                    <select
                      value={task.status}
                      onChange={(e) =>
                        setTasks((prev) =>
                          prev.map((t) => (t.id === task.id ? { ...t, status: e.target.value } : t))
                        )
                      }
                    >
                      <option value="Todo">Todo</option>
                      <option value="In Progress">In Progress</option>
                      <option value="Done">Done</option>
                    </select>
                  </td>
                  <td>
                    <input
                      type="date"
                      value={task.due_date || ""}
                      onChange={(e) =>
                        setTasks((prev) =>
                          prev.map((t) => (t.id === task.id ? { ...t, due_date: e.target.value } : t))
                        )
                      }
                    />
                  </td>
                  <td>{task.team_id}</td>
                  <td>{task.assignee_name || "Unassigned"}</td>
                  <td>
                    <button type="button" onClick={() => updateTask(task)}>
                      Save
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <section className="panel">
        <h2>Task Chat</h2>
        {!selectedTaskId && <p>Select a task to open its conversation.</p>}
        {selectedTaskId && (
          <>
            <div className="comment-list">
              {comments.map((comment) => (
                <article key={comment.id}>
                  <p className="comment-meta">
                    {comment.name} · {new Date(comment.created_at).toLocaleString()}
                  </p>
                  <p>{comment.body}</p>
                </article>
              ))}
            </div>
            <form onSubmit={addComment} className="stack">
              <textarea
                placeholder="Write a comment..."
                value={commentBody}
                onChange={(e) => setCommentBody(e.target.value)}
                required
              />
              <button type="submit">Post Comment</button>
            </form>
          </>
        )}
      </section>
    </main>
  );
}

export default App;
