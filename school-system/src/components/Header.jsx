function Header() {
  return (
    <header style={styles.header}>
      <h1 style={styles.logo}>SchoolMS</h1>

      <nav>
        <ul style={styles.nav}>
          <li>Dashboard</li>
          <li>Students</li>
          <li>Teachers</li>
          <li>Attendance</li>
        </ul>
      </nav>
    </header>
  );
}

const styles = {
  header: {
    display: "flex",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "#1e3a8a",
    color: "white",
    padding: "15px 30px"
  },
  logo: {
    margin: 0
  },
  nav: {
    listStyle: "none",
    display: "flex",
    gap: "20px",
    margin: 0
  }
};

export default Header;