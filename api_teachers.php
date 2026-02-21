<?php
header('Content-Type: application/json');
include 'db_connect.php';

$teachers = [];
$result = $conn->query("SELECT name, image, qualifications, bio FROM teachers WHERE status = 'active' ORDER BY created_at ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            'name' => $row['name'],
            'image' => $row['image'],
            'qualifications' => $row['qualifications'],
            'bio' => strip_tags($row['bio'])
        ];
    }
}

echo json_encode($teachers);
?>
