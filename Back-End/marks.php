<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

function gradeMarks(float $marks): array
{
    if ($marks >= 80) {
        return ["A", "Excellent"];
    }
    if ($marks >= 70) {
        return ["B", "Very good"];
    }
    if ($marks >= 60) {
        return ["C", "Good"];
    }
    if ($marks >= 50) {
        return ["D", "Fair"];
    }
    return ["E", "Needs improvement"];
}

$payload = requireAuth();
$userId = (int) $payload["sub"];
$method = $_SERVER["REQUEST_METHOD"];
$role = normalizeRole((string) ($payload["role"] ?? ""));

if (!isset($payload["role"])) {
    $userStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    if ($userResult->num_rows !== 1) {
        respond(404, ["status" => "error", "message" => "User not found"]);
    }
    $currentUser = $userResult->fetch_assoc();
    $role = (string) $currentUser["role"];
}

if ($method === "GET") {
    if (isset($_GET["students"])) {
        if (!in_array($role, ["admin", "lecturer"], true)) {
            respond(403, ["status" => "error", "message" => "Only admins and lecturers can list students"]);
        }

        $studentsResult = $conn->query(
            "SELECT id, name, email, admission_number
             FROM users
             WHERE role = 'student'
             ORDER BY name ASC"
        );
        $students = [];
        while ($row = $studentsResult->fetch_assoc()) {
            $students[] = $row;
        }
        respond(200, ["status" => "success", "students" => $students]);
    }

    $where = "";
    $params = [];
    $types = "";
    if ($role === "student") {
        $where = "WHERE sm.student_id = ?";
        $params[] = $userId;
        $types .= "i";
    }

    $query = "
        SELECT sm.id, sm.subject, sm.marks, sm.grade, sm.remarks, sm.updated_at,
               student.id AS student_id, student.name AS student_name, student.admission_number,
               lecturer.name AS lecturer_name
        FROM student_marks sm
        INNER JOIN users student ON student.id = sm.student_id
        INNER JOIN users lecturer ON lecturer.id = sm.lecturer_id
        $where
        ORDER BY student.name ASC, sm.subject ASC
    ";
    $stmt = $conn->prepare($query);
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $marks = [];
    while ($row = $result->fetch_assoc()) {
        $marks[] = $row;
    }
    respond(200, ["status" => "success", "marks" => $marks]);
}

if ($method === "POST") {
    if (!in_array($role, ["admin", "lecturer"], true)) {
        respond(403, ["status" => "error", "message" => "Only admins and lecturers can enter marks"]);
    }

    $data = readJsonBody();
    $studentId = (int) ($data["studentId"] ?? 0);
    $subject = trimLimitedString($data["subject"] ?? "", 120);
    $marks = (float) ($data["marks"] ?? -1);

    if ($studentId < 1 || $subject === "" || $marks < 0 || $marks > 100) {
        respond(422, ["status" => "error", "message" => "Student, subject, and marks from 0 to 100 are required"]);
    }

    $studentCheck = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
    $studentCheck->bind_param("i", $studentId);
    $studentCheck->execute();
    if ($studentCheck->get_result()->num_rows !== 1) {
        respond(404, ["status" => "error", "message" => "Student not found"]);
    }

    [$grade, $remarks] = gradeMarks($marks);
    $stmt = $conn->prepare(
        "INSERT INTO student_marks (student_id, lecturer_id, subject, marks, grade, remarks)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            lecturer_id = VALUES(lecturer_id),
            marks = VALUES(marks),
            grade = VALUES(grade),
            remarks = VALUES(remarks)"
    );
    $stmt->bind_param("iisdss", $studentId, $userId, $subject, $marks, $grade, $remarks);
    $stmt->execute();

    respond(200, [
        "status" => "success",
        "message" => "Marks saved",
        "grade" => $grade,
        "remarks" => $remarks
    ]);
}

respond(405, ["status" => "error", "message" => "Method not allowed"]);
?>
