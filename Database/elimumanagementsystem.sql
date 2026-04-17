CREATE DATABASE IF NOT EXISTS elimumanagementsystem;
USE elimumanagementsystem;

-- ========================
-- STUDENTS TABLE (FIXED)
-- ========================
CREATE TABLE students (
  StudentID INT AUTO_INCREMENT PRIMARY KEY,
  admission_number VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  FirstName VARCHAR(50) NOT NULL,
  LastName VARCHAR(50) NOT NULL,
  DateOfBirth DATE DEFAULT NULL,
  Gender ENUM('M','F','O') DEFAULT NULL,
  Email VARCHAR(100) UNIQUE,
  Phone VARCHAR(15),
  Address TEXT,
  EnrollmentDate DATE DEFAULT CURRENT_DATE,
  Status ENUM('Active','Inactive','Graduated') DEFAULT 'Active'
);

-- ========================
-- SAMPLE LOGIN DATA
-- ========================
INSERT INTO students (admission_number, password, FirstName, LastName)
VALUES 
('26/419', '12345', 'Elvis', 'Student'),
('26/420', '12345', 'Test', 'User');

-- ========================
-- CLASSES TABLE
-- ========================
CREATE TABLE classes (
  ClassID INT AUTO_INCREMENT PRIMARY KEY,
  ClassName VARCHAR(20) NOT NULL,
  Section VARCHAR(5),
  Capacity INT DEFAULT 40
);

INSERT INTO classes (ClassName, Section) VALUES
('Class 10', 'A'),
('Class 10', 'B'),
('Class 11', 'A');

-- ========================
-- SUBJECTS TABLE
-- ========================
CREATE TABLE subjects (
  SubjectID INT AUTO_INCREMENT PRIMARY KEY,
  SubjectName VARCHAR(50) NOT NULL,
  SubjectCode VARCHAR(10) UNIQUE,
  Credits INT DEFAULT 3,
  ClassID INT,
  FOREIGN KEY (ClassID) REFERENCES classes(ClassID)
);

-- ========================
-- STUDENT CLASS
-- ========================
CREATE TABLE studentclass (
  EnrollmentID INT AUTO_INCREMENT PRIMARY KEY,
  StudentID INT,
  ClassID INT,
  EnrollmentDate DATE DEFAULT CURRENT_DATE,
  Status ENUM('Enrolled','Dropped') DEFAULT 'Enrolled',
  FOREIGN KEY (StudentID) REFERENCES students(StudentID),
  FOREIGN KEY (ClassID) REFERENCES classes(ClassID)
);

-- ========================
-- MARKS TABLE
-- ========================
CREATE TABLE marks (
  MarkID INT AUTO_INCREMENT PRIMARY KEY,
  StudentID INT,
  SubjectID INT,
  ClassID INT,
  ExamType ENUM('Midterm','Final','Quiz','Assignment'),
  MarksObtained DECIMAL(5,2),
  TotalMarks DECIMAL(5,2) DEFAULT 100.00,
  Grade VARCHAR(5),
  ExamDate DATE,
  FOREIGN KEY (StudentID) REFERENCES students(StudentID),
  FOREIGN KEY (SubjectID) REFERENCES subjects(SubjectID),
  FOREIGN KEY (ClassID) REFERENCES classes(ClassID)
);

-- ========================
-- ATTENDANCE TABLE
-- ========================
CREATE TABLE attendance (
  AttendanceID INT AUTO_INCREMENT PRIMARY KEY,
  StudentID INT,
  ClassID INT,
  SubjectID INT,
  AttendanceDate DATE,
  Status ENUM('Present','Absent','Late') DEFAULT 'Present',
  FOREIGN KEY (StudentID) REFERENCES students(StudentID),
  FOREIGN KEY (ClassID) REFERENCES classes(ClassID),
  FOREIGN KEY (SubjectID) REFERENCES subjects(SubjectID)
);

-- ========================
-- FEES TABLE
-- ========================
CREATE TABLE fees (
  FeeID INT AUTO_INCREMENT PRIMARY KEY,
  StudentID INT,
  FeeType VARCHAR(50),
  Amount DECIMAL(10,2),
  DueDate DATE,
  PaidDate DATE,
  Status ENUM('Paid','Pending','Overdue') DEFAULT 'Pending',
  FOREIGN KEY (StudentID) REFERENCES students(StudentID)
);