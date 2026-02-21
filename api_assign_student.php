<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'search_students') {
    $query = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
    
    $sql = "SELECT id, first_name, last_name, grade FROM students";
    if (!empty($query)) {
        $sql .= " WHERE first_name LIKE '%$query%' OR last_name LIKE '%$query%'";
    }
    $sql .= " ORDER BY first_name ASC LIMIT 20";
    
    $result = $conn->query($sql);
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $row['formatted_grade'] = format_grade($row['grade']);
        $students[] = $row;
    }
    echo json_encode(['success' => true, 'students' => $students]);
} 

elseif ($action === 'get_enrollments') {
    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
    
    $sql = "SELECT c.id, c.grade, c.subject 
            FROM student_enrollments se 
            JOIN classes c ON se.class_id = c.id 
            WHERE se.student_id = $student_id";
    
    $result = $conn->query($sql);
    $enrollments = [];
    while ($row = $result->fetch_assoc()) {
        $row['formatted_grade'] = format_grade($row['grade']);
        $enrollments[] = $row;
    }
    
    $all_classes = [];
    $cls_res = $conn->query("SELECT id, grade, subject FROM classes ORDER BY grade ASC, subject ASC");
    while ($row = $cls_res->fetch_assoc()) {
        $row['formatted_grade'] = format_grade($row['grade']);
        $all_classes[] = $row;
    }
    
    echo json_encode(['success' => true, 'enrollments' => $enrollments, 'all_classes' => $all_classes]);
}

elseif ($action === 'assign') {
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    
    if ($student_id && $class_id) {
        $stmt = $conn->prepare("INSERT IGNORE INTO student_enrollments (student_id, class_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $student_id, $class_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Assigned successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error assigning: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}

elseif ($action === 'remove') {
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    
    if ($student_id && $class_id) {
        $stmt = $conn->prepare("DELETE FROM student_enrollments WHERE student_id = ? AND class_id = ?");
        $stmt->bind_param("ii", $student_id, $class_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error removing: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}
?>
