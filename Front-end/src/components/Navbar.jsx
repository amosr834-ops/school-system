import React, { useState } from "react";
import Logo from "../Assets/Logo.png"
import redcross from "../Assets/Red Cross, White Circle.jpg"

function Navbar() {

    return (
 <>
      
        <div className="overhead">
        <header>
            <div>
            <h1>ELIMU SCHOOL STUDENTS MANAGEMENT SYSTEM</h1>
            <p className="motto">Motto: Clearly Different</p>
            </div>
            <button>Log out</button>
        </header>
          <div className="nav-container">
            <nav>
            <ul>
                <li><button>Profile</button></li>
                <li><button>Academic Performance</button></li>
                <li><button>Social Groups</button></li>
                <li><button>Communities</button></li>
                <li><button>Disciplinary</button></li>
                <li><button>Co-Curricular</button></li>
            </ul>
            </nav>
          <div className="Logobox">
            <img src={Logo} alt="Logo" />
        </div>
        </div>
    </div>
 </>


    );
}

export default Navbar;
