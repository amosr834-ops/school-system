const express = require("express");

const app = express();
app.use(express.json());

app.post("/api/login", (req, res) => {
  res.status(410).json({ message: "Use the PHP API login endpoint." });
});

app.listen(5000);
