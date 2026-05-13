CREATE DATABASE IF NOT EXISTS collaborative_tasks;
USE collaborative_tasks;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  admission_number VARCHAR(50) NULL UNIQUE,
  role ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student',
  google_sub VARCHAR(255) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_users_role_name (role, name)
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

CREATE TABLE IF NOT EXISTS student_marks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  lecturer_id INT NOT NULL,
  subject VARCHAR(120) NOT NULL,
  marks DECIMAL(5,2) NOT NULL,
  grade VARCHAR(5) NOT NULL,
  remarks VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_student_subject (student_id, subject),
  KEY idx_student_marks_lecturer (lecturer_id),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample test data ----------------------------------------------------------
-- Login credentials for all seeded users:
-- email: see records below
-- password: password

INSERT INTO users (id, name, email, admission_number, role, google_sub, password_hash, created_at)
VALUES
  (1, 'Alice Admin', 'alice.admin@school.local', NULL, 'admin', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:00:00'),
  (2, 'Brian Lecturer', 'brian.lecturer@school.local', NULL, 'lecturer', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:05:00'),
  (3, 'Carol Lecturer', 'carol.lecturer@school.local', NULL, 'lecturer', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:10:00'),
  (4, 'David Student', 'david.student@school.local', '20/194', 'student', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:15:00'),
  (5, 'Emma Student', 'emma.student@school.local', '20/195', 'student', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-01 08:20:00')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  email = VALUES(email),
  admission_number = VALUES(admission_number),
  role = VALUES(role),
  google_sub = VALUES(google_sub),
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

INSERT INTO student_marks (id, student_id, lecturer_id, subject, marks, grade, remarks, created_at, updated_at)
VALUES
  (3001, 4, 2, 'Mathematics', 82, 'A', 'Excellent', '2026-04-20 10:00:00', '2026-04-20 10:00:00'),
  (3002, 4, 2, 'English', 74, 'B', 'Very good', '2026-04-20 10:10:00', '2026-04-20 10:10:00'),
  (3003, 5, 3, 'Science', 67, 'C', 'Good', '2026-04-20 10:20:00', '2026-04-20 10:20:00')
ON DUPLICATE KEY UPDATE
  lecturer_id = VALUES(lecturer_id),
  marks = VALUES(marks),
  grade = VALUES(grade),
  remarks = VALUES(remarks),
  updated_at = VALUES(updated_at);
