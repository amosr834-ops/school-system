import React from "react";
import PasswordReset from "./components/password";
import Login from "./components/Login";
import Navbar from "./components/Navbar";
import Profile from "./pages/Profile";
import Academic from "./pages/Academic";
import {BrowserRouter,Routes,Route,Link} from "react-router-dom";

function App() {
  return (
    <BrowserRouter>
    //navigation 
    <nav>
    <Link to="/">Login</Link>
    <Link to="/password">Password</Link>
    <Link to="/">Login</Link>
    <Link to="/">Login</Link>
    </nav>
    //setting up the routes
    <Routes>
    <Route path="/" element={Login}/>
    <Route path="/password" element={PasswordReset}/>
    //new routes will be added
    </Routes>

    </BrowserRouter>
      
    
  );
}

export default App;


