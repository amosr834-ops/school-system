import React from "react";
import { useNavigate } from "react-router-dom";
import Logo from "../Assets/Logo.png";

function Navbar() {
  const navigate = useNavigate();

  return (
    <>
      <div className="overhead">
        <header>
          <div>
            <h1>ELIMU SCHOOL STUDENTS MANAGEMENT SYSTEM</h1>
            <p className="motto">Motto: Clearly Different</p>
          </div>
          <button onClick={() => navigate("/")}>Log out</button>
        </header>
      </div>
      <div className="nav-container">
        <div className="Logobox">
          <img src={Logo} alt="Logo" />
        </div>
      </div>
    </>
  );
}

export default Navbar;
