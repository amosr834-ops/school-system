import React, { useState } from "react";
import axios from "axios";
import "./Login.css";

function Login() {
  const [admission, setAdmission] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");

  const handleLogin = async () => {
    try {
      const res = await axios.post("http://localhost:5000/api/login", {
        admission,
        password,
      });

      alert("Login successful ✅");
      console.log(res.data);

      // later: redirect user
    } catch (err) {
      setError("Invalid admission number or password ❌");
    }
  };

  const handleReset = () => {
    setAdmission("");
    setPassword("");
    setError("");
  };

  return (
    <div className="wrapper">
      <div className="card">

        {/* LEFT */}
        <div className="left">
          <h1 className="school-name">ELIMU SCHOOL</h1>

          <img
            src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png"
            alt="logo"
            className="logo-img"
          />

          <p className="tagline">Clearly Different</p>
        </div>

        {/* RIGHT */}
        <div className="right">
          <h1 className="title">Log in</h1>

          <label>Admission number</label>
          <input
            type="text"
            value={admission}
            onChange={(e) => setAdmission(e.target.value)}
            placeholder="26/419"
          />

          <label>Password</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="********"
          />

          <a href="#">Forgot password?</a>

          {error && <p className="error">{error}</p>}

          <button onClick={handleLogin}>SIGN IN</button>

          <p className="reset" onClick={handleReset}>Reset</p>
        </div>

      </div>
    </div>
  );
}

export default Login;