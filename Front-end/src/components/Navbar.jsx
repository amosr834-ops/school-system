import React, { useState } from "react";
import logo from "../Assets/Logo.png"

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
            <main>
            <div className="profile-card">
                        <img src={redcross} alt="Student Photo" className="studentprofilephoto"/>
                <h1>STUDENT DETAILS</h1>                <p><strong>Name: </strong>Brandon Mamillo</p>
                <p><strong>Adm.no: </strong> 20/194</p>
                <p><strong>Date of Birth: </strong> 12/01/2008</p>
                <p><strong>Gender: </strong> Male</p>
                <p><strong>Grade: </strong> 7</p>
                <p><strong>Parent/Guardian: </strong> Harry Porter</p>
                <p><strong>Email Address: </strong> Brandonmamillo@gmail.com</p>
            </div>
            </main>
        </div>
    </div>
 </>


    );
}

export default Navbar;
