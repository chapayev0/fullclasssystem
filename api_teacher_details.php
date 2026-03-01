<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['teacher_id'])) {
    $teacher_id = intval($_GET['teacher_id']);
    
    // Fetch teacher info
    $t_stmt = $conn->prepare("SELECT t.*, u.username, u.email as user_email FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $t_stmt->bind_param("i", $teacher_id);
    $t_stmt->execute();
    $teacher = $t_stmt->get_result()->fetch_assoc();
    
    if (!$teacher) {
        echo json_encode(['error' => 'Teacher not found']);
        exit();
    }
    
    // Fetch assigned classes (via subjects)
    $c_stmt = $conn->prepare("SELECT c.subject, c.grade, c.class_day, c.start_time, c.end_time 
                             FROM classes c 
                             JOIN subjects s ON c.subject = s.name 
                             WHERE s.teacher_id = ?");
    $c_stmt->bind_param("i", $teacher_id);
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
        'teacher' => $teacher,
        'classes' => $classes
    ]);
} elseif (isset($_GET['username'])) {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    
    // Fetch teacher info by username (for QR scan lookup)
    $t_stmt = $conn->prepare("SELECT t.*, u.username, u.email as user_email FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.username = ?");
    $t_stmt->bind_param("s", $username);
    $t_stmt->execute();
    $teacher = $t_stmt->get_result()->fetch_assoc();
    
    if (!$teacher) {
        echo json_encode(['error' => 'Teacher not found']);
        exit();
    }
    
    $teacher_id = $teacher['id'];
    
    // Fetch assigned classes
    $c_stmt = $conn->prepare("SELECT c.subject, c.grade, c.class_day, c.start_time, c.end_time 
                             FROM classes c 
                             JOIN subjects s ON c.subject = s.name 
                             WHERE s.teacher_id = ?");
    $c_stmt->bind_param("i", $teacher_id);
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
        'teacher' => $teacher,
        'classes' => $classes
    ]);
} else {
    echo json_encode(['error' => 'Missing parameters']);
}
