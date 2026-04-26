import React from "react";
import redcross from "../Assets/Red Cross, White Circle.jpg";

function Profile() {

    return (
        <>
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
        </>
    );
}
export default Profile;
