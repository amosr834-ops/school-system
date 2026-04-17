-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 10:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elimumanagementsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `ClassID` int(11) DEFAULT NULL,
  `SubjectID` int(11) DEFAULT NULL,
  `AttendanceDate` date DEFAULT NULL,
  `Status` enum('Present','Absent','Late') DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `ClassID` int(11) NOT NULL,
  `ClassName` varchar(20) NOT NULL,
  `Section` varchar(5) DEFAULT NULL,
  `TeacherID` int(11) DEFAULT NULL,
  `Capacity` int(11) DEFAULT 40
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`ClassID`, `ClassName`, `Section`, `TeacherID`, `Capacity`) VALUES
(1, 'Class 10', 'A', 1, 40),
(2, 'Class 10', 'B', 2, 40),
(3, 'Class 11', 'A', 3, 35);

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `FeeID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `FeeType` varchar(50) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `PaidDate` date DEFAULT NULL,
  `Status` enum('Paid','Pending','Overdue') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `MarkID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `SubjectID` int(11) DEFAULT NULL,
  `ClassID` int(11) DEFAULT NULL,
  `ExamType` enum('Midterm','Final','Quiz','Assignment') DEFAULT NULL,
  `MarksObtained` decimal(5,2) DEFAULT NULL,
  `TotalMarks` decimal(5,2) DEFAULT 100.00,
  `Grade` varchar(5) DEFAULT NULL,
  `ExamDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`MarkID`, `StudentID`, `SubjectID`, `ClassID`, `ExamType`, `MarksObtained`, `TotalMarks`, `Grade`, `ExamDate`) VALUES
(1, 1, 1, 1, 'Final', 85.50, 100.00, 'A', NULL),
(2, 2, 1, 1, 'Final', 92.00, 100.00, 'A+', NULL),
(3, 1, 2, 1, 'Final', 78.25, 100.00, 'B+', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `studentclass`
--

CREATE TABLE `studentclass` (
  `EnrollmentID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `ClassID` int(11) DEFAULT NULL,
  `EnrollmentDate` date DEFAULT curdate(),
  `Status` enum('Enrolled','Dropped') DEFAULT 'Enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentclass`
--

INSERT INTO `studentclass` (`EnrollmentID`, `StudentID`, `ClassID`, `EnrollmentDate`, `Status`) VALUES
(1, 1, 1, '2026-04-08', 'Enrolled'),
(2, 2, 1, '2026-04-08', 'Enrolled'),
(3, 3, 2, '2026-04-08', 'Enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `StudentID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `Gender` enum('M','F','O') DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `EnrollmentDate` date DEFAULT curdate(),
  `Status` enum('Active','Inactive','Graduated') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`StudentID`, `FirstName`, `LastName`, `DateOfBirth`, `Gender`, `Email`, `Phone`, `Address`, `EnrollmentDate`, `Status`) VALUES
(1, 'Alice', 'Williams', '2008-05-15', 'F', 'alice.w@school.com', '1112223333', NULL, '2026-04-08', 'Active'),
(2, 'Bob', 'Davis', '2008-03-22', 'M', 'bob.d@school.com', '4445556666', NULL, '2026-04-08', 'Active'),
(3, 'Carol', 'Wilson', '2007-11-10', 'F', 'carol.w@school.com', '7778889999', NULL, '2026-04-08', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `SubjectID` int(11) NOT NULL,
  `SubjectName` varchar(50) NOT NULL,
  `SubjectCode` varchar(10) DEFAULT NULL,
  `Credits` int(11) DEFAULT 3,
  `ClassID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`SubjectID`, `SubjectName`, `SubjectCode`, `Credits`, `ClassID`) VALUES
(1, 'Mathematics', 'MATH101', 4, 1),
(2, 'English', 'ENG101', 3, 1),
(3, 'Physics', 'PHY101', 4, 3);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `TeacherID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Subject` varchar(50) DEFAULT NULL,
  `Department` varchar(50) DEFAULT NULL,
  `HireDate` date DEFAULT curdate(),
  `Status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`TeacherID`, `FirstName`, `LastName`, `Email`, `Phone`, `Subject`, `Department`, `HireDate`, `Status`) VALUES
(1, 'John', 'Smith', 'john.smith@school.com', '1234567890', 'Mathematics', 'Science', '2026-04-08', 'Active'),
(2, 'Sarah', 'Johnson', 'sarah.j@school.com', '0987654321', 'English', 'Languages', '2026-04-08', 'Active'),
(3, 'Mike', 'Brown', 'mike.brown@school.com', '1122334455', 'Physics', 'Science', '2026-04-08', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `ClassID` (`ClassID`),
  ADD KEY `SubjectID` (`SubjectID`),
  ADD KEY `idx_attendance_date` (`AttendanceDate`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`ClassID`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`FeeID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `idx_fees_status` (`Status`,`DueDate`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`MarkID`),
  ADD KEY `SubjectID` (`SubjectID`),
  ADD KEY `ClassID` (`ClassID`),
  ADD KEY `idx_marks_student` (`StudentID`);

--
-- Indexes for table `studentclass`
--
ALTER TABLE `studentclass`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD KEY `ClassID` (`ClassID`),
  ADD KEY `idx_student_class` (`StudentID`,`ClassID`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`StudentID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_student_name` (`FirstName`,`LastName`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`SubjectID`),
  ADD UNIQUE KEY `SubjectCode` (`SubjectCode`),
  ADD KEY `ClassID` (`ClassID`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`TeacherID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `ClassID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `FeeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `MarkID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `studentclass`
--
ALTER TABLE `studentclass`
  MODIFY `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `StudentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `SubjectID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `TeacherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `students` (`StudentID`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`ClassID`) REFERENCES `classes` (`ClassID`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`SubjectID`) REFERENCES `subjects` (`SubjectID`);

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `students` (`StudentID`);

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `students` (`StudentID`),
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`SubjectID`) REFERENCES `subjects` (`SubjectID`),
  ADD CONSTRAINT `marks_ibfk_3` FOREIGN KEY (`ClassID`) REFERENCES `classes` (`ClassID`);

--
-- Constraints for table `studentclass`
--
ALTER TABLE `studentclass`
  ADD CONSTRAINT `studentclass_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `students` (`StudentID`),
  ADD CONSTRAINT `studentclass_ibfk_2` FOREIGN KEY (`ClassID`) REFERENCES `classes` (`ClassID`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`ClassID`) REFERENCES `classes` (`ClassID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
