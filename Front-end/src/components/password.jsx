import React, { useState } from "react";
import logo from "../Assets/Logo.png";

function PasswordReset() {
  const [step, setStep] = useState(1);
  const [email, setEmail] = useState("");
  const [code, setCode] = useState("");
  const [newPassword, setNewPassword] = useState("");

  const handleSendCode = (e) => {
    e.preventDefault();
    setStep(2);
  };

  const handleVerifyCode = (e) => {
    e.preventDefault();
    setStep(3);
  };

  const handleSetPassword = (e) => {
    e.preventDefault();
    window.location.href = "/login";
  };

  return (
    <div className="login-container">
      <div className="left-panel">
        <h1>ELIMU SCHOOL</h1>
        <img src={logo} alt="Elimu School Logo" />
      </div>

      <div className="right-panel">
        <div className="login-box">
          <h2>Reset Password</h2>

          {step === 1 && (
            <form onSubmit={handleSendCode}>
              <label>Email Address</label>
              <input
                type="email"
                placeholder="johndoe@gmail.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
              <button className="signin" type="submit">Get Code</button>
            </form>
          )}

          {step === 2 && (
            <form onSubmit={handleVerifyCode}>
              <label>Enter Code</label>
              <input
                type="number"
                placeholder="123456"
                value={code}
                onChange={(e) => setCode(e.target.value)}
                required
              />
              <button className="signin" type="submit">Verify Code</button>
            </form>
          )}

          {step === 3 && (
            <form onSubmit={handleSetPassword}>
              <label>New Password</label>
              <input
                type="password"
                placeholder="*****"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                required
              />
              <button className="signin" type="submit">Confirm Password</button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}

export default PasswordReset;
