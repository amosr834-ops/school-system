// src/components/Sidebar.jsx
import React from "react";
import { useNavigate } from "react-router-dom";

function Sidebar() {
  const navigate = useNavigate();

  return (
    <div className="nav-container">
      <nav>
        <ul>
          <li>
            <button onClick={() => navigate("/dashboard")}>Profile</button>
          </li>
          <li>
            <button onClick={() => navigate("/academic")}>Academic Performance</button>
          </li>
          <li>
            <button onClick={() => navigate("/dashboard")}>Social Groups</button>
          </li>
          <li>
            <button onClick={() => navigate("/dashboard")}>Communities</button>
          </li>
          <li>
            <button onClick={() => navigate("/dashboard")}>Disciplinary</button>
          </li>
          <li>
            <button onClick={() => navigate("/dashboard")}>Co-Curricular</button>
          </li>
        </ul>
      </nav>
    </div>
  );
}

export default Sidebar;
