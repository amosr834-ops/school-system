import React, { useState } from "react";
import axios from "axios";
import { useNavigate } from "react-router-dom";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "/api";
const backgroundImage = "/background.png";

function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const navigate = useNavigate();
  const loginBackgroundStyle = {
    minHeight: "100vh",
    display: "flex",
    backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), url(${backgroundImage})`,
    backgroundSize: "cover",
    backgroundPosition: "center",
    backgroundRepeat: "no-repeat",
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const response = await axios.post(
        `${API_BASE_URL}/Login.php`,
        {
          email,
          password,
        },
        {
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      if (response.data.status === "success") {
        if (response.data.token) {
          sessionStorage.setItem("token", response.data.token);
          localStorage.removeItem("token");
        }
        navigate("/dashboard");
      } else {
        alert(response.data.message);
      }
    } catch {
      alert("Server error");
    }
  };

  const handleReset = () => {
    setEmail("");
    setPassword("");
  };

  return (
    <div className="login-container" style={loginBackgroundStyle}>
      {/* Left panel with logo and slogan */}
      <div className="left-panel">
        
        <h1>ELIMU SCHOOL</h1>
        <img src={backgroundImage} alt="Elimu School Logo" />
      
      </div>

      {/* Right panel with login form */}
      <div className="right-panel">
        <div className="login-box">
          <h2>Log in</h2>
          <form onSubmit={handleSubmit}>
            <label htmlFor="email">Email</label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="e.g. alice.admin@school.local"
              required
            />

            <label htmlFor="password">Password</label>
            <input
              type="password"
              id="password"
              placeholder="*****"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />

            <a href="/password">Forgot password?</a>

            <button className="signin" type="submit">SIGN IN</button>
            <button
              type="button"
              className="reset-btn"
              onClick={handleReset}
            >
              Reset
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}

export default Login;
