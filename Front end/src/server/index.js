const express = require("express");
const cors = require("cors");

const app = express();
app.use(cors());
app.use(express.json());

// fake user (for now)
const user = {
  admission: "26/419",
  password: "1234"
};

app.post("/api/login", (req, res) => {
  const { admission, password } = req.body;

  if (admission === user.admission && password === user.password) {
    res.json({ message: "Login successful" });
  } else {
    res.status(401).json({ message: "Invalid credentials" });
  }
});

app.listen(5000, () => {
  console.log("Server running on port 5000");
});