CREATE DATABASE IF NOT EXISTS collaborative_tasks;
USE collaborative_tasks;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  owner_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS team_members (
  team_id INT NOT NULL,
  user_id INT NOT NULL,
  role ENUM('owner', 'member') DEFAULT 'member',
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (team_id, user_id),
  FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  status ENUM('Todo', 'In Progress', 'Done') DEFAULT 'Todo',
  priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
  due_date DATE DEFAULT NULL,
  created_by INT NOT NULL,
  assignee_id INT DEFAULT NULL,
  team_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS task_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(255) NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample test data ----------------------------------------------------------
-- Login credentials for all seeded users:
-- email: see records below
-- password: password

INSERT INTO users (id, name, email, password_hash, created_at)
VALUES
  (1, 'Alice Admin', 'alice.admin@school.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:00:00'),
  (2, 'Brian Teacher', 'brian.teacher@school.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:05:00'),
  (3, 'Carol Teacher', 'carol.teacher@school.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:10:00'),
  (4, 'David Student', 'david.student@school.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:15:00'),
  (5, 'Emma Student', 'emma.student@school.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:20:00')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  email = VALUES(email),
  password_hash = VALUES(password_hash);

INSERT INTO teams (id, name, owner_id, created_at)
VALUES
  (1, 'Science Department', 1, '2026-04-02 09:00:00'),
  (2, 'Student Council', 2, '2026-04-02 09:10:00'),
  (3, 'Sports Club', 3, '2026-04-02 09:20:00')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  owner_id = VALUES(owner_id);

INSERT INTO team_members (team_id, user_id, role, joined_at)
VALUES
  (1, 1, 'owner', '2026-04-02 09:00:00'),
  (1, 2, 'member', '2026-04-02 09:05:00'),
  (1, 3, 'member', '2026-04-02 09:06:00'),
  (2, 2, 'owner', '2026-04-02 09:10:00'),
  (2, 4, 'member', '2026-04-02 09:12:00'),
  (2, 5, 'member', '2026-04-02 09:13:00'),
  (3, 3, 'owner', '2026-04-02 09:20:00'),
  (3, 4, 'member', '2026-04-02 09:25:00'),
  (3, 5, 'member', '2026-04-02 09:26:00'),
  (3, 1, 'member', '2026-04-02 09:27:00')
ON DUPLICATE KEY UPDATE
  role = VALUES(role),
  joined_at = VALUES(joined_at);

INSERT INTO tasks (id, title, description, status, priority, due_date, created_by, assignee_id, team_id, created_at, updated_at)
VALUES
  (101, 'Prepare Midterm Question Bank', 'Compile Biology and Chemistry questions for Grade 10.', 'In Progress', 'High', '2026-05-10', 1, 2, 1, '2026-04-10 11:00:00', '2026-04-12 14:30:00'),
  (102, 'Review Lab Safety Checklist', 'Confirm all consumables and emergency kits are available.', 'Todo', 'Medium', '2026-05-03', 2, 3, 1, '2026-04-11 10:15:00', '2026-04-11 10:15:00'),
  (103, 'Publish Debate Event Notice', 'Share debate dates and participant guidelines with students.', 'Done', 'Low', '2026-04-20', 2, 4, 2, '2026-04-08 16:00:00', '2026-04-20 09:00:00'),
  (104, 'Collect Sports Equipment Requests', 'Gather team needs before budget meeting.', 'In Progress', 'Medium', '2026-05-06', 3, 5, 3, '2026-04-14 08:45:00', '2026-04-18 15:10:00'),
  (105, 'Finalize Inter-house Fixtures', 'Prepare final football and basketball fixture schedule.', 'Todo', 'High', '2026-05-15', 3, 4, 3, '2026-04-15 13:10:00', '2026-04-15 13:10:00'),
  (106, 'Update Team Contact Sheet', 'Refresh teacher phone numbers for science department.', 'Todo', 'Low', '2026-05-01', 1, NULL, 1, '2026-04-13 09:30:00', '2026-04-13 09:30:00')
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  description = VALUES(description),
  status = VALUES(status),
  priority = VALUES(priority),
  due_date = VALUES(due_date),
  created_by = VALUES(created_by),
  assignee_id = VALUES(assignee_id),
  team_id = VALUES(team_id),
  updated_at = VALUES(updated_at);

INSERT INTO task_comments (id, task_id, user_id, body, created_at)
VALUES
  (1001, 101, 2, 'Draft is ready. I am adding practical questions this afternoon.', '2026-04-11 13:05:00'),
  (1002, 101, 1, 'Great. Please include one section for revision exercises.', '2026-04-11 14:10:00'),
  (1003, 104, 5, 'I have added a request for two extra footballs and bibs.', '2026-04-16 12:30:00'),
  (1004, 105, 4, 'Waiting for athletics teacher confirmation before final publish.', '2026-04-17 09:40:00')
ON DUPLICATE KEY UPDATE
  task_id = VALUES(task_id),
  user_id = VALUES(user_id),
  body = VALUES(body),
  created_at = VALUES(created_at);

INSERT INTO notifications (id, user_id, message, is_read, created_at)
VALUES
  (2001, 2, 'A task was assigned to you: Prepare Midterm Question Bank', 0, '2026-04-10 11:00:10'),
  (2002, 3, 'A task was assigned to you: Review Lab Safety Checklist', 0, '2026-04-11 10:15:10'),
  (2003, 4, 'A task was assigned to you: Finalize Inter-house Fixtures', 1, '2026-04-15 13:10:10'),
  (2004, 5, 'A task was assigned to you: Collect Sports Equipment Requests', 0, '2026-04-14 08:45:10'),
  (2005, 1, 'New comment on task #101', 1, '2026-04-11 14:10:10')
ON DUPLICATE KEY UPDATE
  user_id = VALUES(user_id),
  message = VALUES(message),
  is_read = VALUES(is_read),
  created_at = VALUES(created_at);
