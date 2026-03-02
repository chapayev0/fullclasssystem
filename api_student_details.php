<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if admin or teacher
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    
    // Fetch student info
    $s_stmt = $conn->prepare("SELECT s.*, u.username, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $s_stmt->bind_param("i", $student_id);
    $s_stmt->execute();
    $student = $s_stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        echo json_encode(['error' => 'Student not found']);
        exit();
    }
    
    // Fetch enrolled classes
    $c_stmt = $conn->prepare("SELECT c.subject, c.grade, c.class_day, c.start_time, c.end_time 
                             FROM student_enrollments se 
                             JOIN classes c ON se.class_id = c.id 
                             WHERE se.student_id = ?");
    $c_stmt->bind_param("i", $student_id);
    $c_stmt->execute();
    $result = $c_stmt->get_result();
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = [
            'subject' => $row['subject'],
            'grade' => $row['grade'],
            'schedule_day' => $row['class_day'],
            'schedule_time' => $row['start_time'] . ' - ' . $row['end_time']
        ];
    }
    
    echo json_encode([
        'student' => $student,
        'classes' => $classes
    ]);
} elseif (isset($_GET['username'])) {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    
    // Fetch student info by username (for QR scan lookup)
    $s_stmt = $conn->prepare("SELECT s.*, u.username, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE u.username = ?");
    $s_stmt->bind_param("s", $username);
    $s_stmt->execute();
    $student = $s_stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        echo json_encode(['error' => 'Student not found']);
        exit();
    }
    
    $student_id = $student['id'];
    
    // Fetch enrolled classes
    $c_stmt = $conn->prepare("SELECT c.subject, c.grade, c.class_day, c.start_time, c.end_time 
                             FROM student_enrollments se 
                             JOIN classes c ON se.class_id = c.id 
                             WHERE se.student_id = ?");
    $c_stmt->bind_param("i", $student_id);
    $c_stmt->execute();
    $result = $c_stmt->get_result();
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = [
            'subject' => $row['subject'],
            'grade' => $row['grade'],
            'schedule_day' => $row['class_day'],
            'schedule_time' => $row['start_time'] . ' - ' . $row['end_time']
        ];
    }
    
    echo json_encode([
        'student' => $student,
        'classes' => $classes
    ]);
} else {
    echo json_encode(['error' => 'Missing parameters']);
}
