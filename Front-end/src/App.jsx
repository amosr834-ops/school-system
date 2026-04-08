import Login from "./pages/Login";

function Header() {
  return (
    <div>
      <h2 style={{ padding: "20px" }}>
        Welcome to Elimu School where we nurture young minds and empower future leaders. Explore our dashboard
      </h2>
    </div>
  );
}

function App() {
  return (
    <div>
      <Header />
      <Login />
    </div>
  );
}

export default App;
