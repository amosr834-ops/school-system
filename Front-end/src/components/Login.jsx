import React, { useState } from "react";
import axios from "axios";
import logo from "../Assets/Logo.png"

function Login() {
  const [admissionNumber, setAdmissionNumber] = useState("");
  const [password, setPassword] = useState("");

  const handleSubmit = async (e) => {
  e.preventDefault();

  try {
    const response = await axios.post(
      "http://localhost/Back-End/Login.php",
      {
        admissionNumber: admissionNumber,
        password: password,
      },
      {
        headers: {
          "Content-Type": "application/json",
        },
      }
    );

    if (response.data.status === "success") {
      alert("Login successful!");
      console.log(response.data);

      // redirect after login
      window.location.href = "/dashboard";
    } else {
      alert(response.data.message);
    }
  } catch (error) {
    console.error(error);
    alert("Server error");
  }
};

  const handleReset = () => {
    setAdmissionNumber("");
    setPassword("");
  };

  return (
    <div className="login-container">
      {/* Left panel with logo and slogan */}
      <div className="left-panel">
        
        <h1>ELIMU SCHOOL</h1>
        <img src={logo} alt="Elimu School Logo" />
      
      </div>

      {/* Right panel with login form */}
      <div className="right-panel">
        <div className="login-box">
          <h2>Log in</h2>
          <form onSubmit={handleSubmit}>
            <label htmlFor="admission">Admission number/TSC Number</label>
            <input
              type="text"
              id="admission"
              value={admissionNumber}
              onChange={(e) => setAdmissionNumber(e.target.value)}
              placeholder="e.g. 26/419 or TSC12345"
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

            <a href="/forgot-password">Forgot password?</a>

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
};

export default Login;
