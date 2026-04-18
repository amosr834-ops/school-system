// src/components/Sidebar.jsx
import React from "react";
import { useNavigate } from "react-router-dom";


function Sidebar() {
  const navigate = useNavigate();

  return (
    <aside className="sidebar">
      <nav>
        <ul>
          <li>
            <button onClick={() => navigate("/")}>Dashboard</button>
          </li>
          <li>
            <button onClick={() => navigate("/profile")}>Profile</button>
          </li>
          <li>
            <button onClick={() => navigate("/academic")}>Academic Performance</button>
          </li>
          <li>
            <button onClick={() => navigate("/social-groups")}>Social Groups</button>
          </li>
          <li>
            <button onClick={() => navigate("/communities")}>Communities</button>
          </li>
          <li>
            <button onClick={() => navigate("/disciplinary")}>Disciplinary</button>
          </li>
          <li>
            <button onClick={() => navigate("/co-curricular")}>Co-Curricular</button>
          </li>
        </ul>
      </nav>
    </aside>
  );
}

export default Sidebar;
