<?php
header('Content-Type: application/json');
include 'db_connect.php';

$teachers = [];
$result = $conn->query("SELECT id, name, image, qualifications, bio, phone, whatsapp, email, website FROM teachers WHERE status = 'active' ORDER BY created_at ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'image' => $row['image'],
            'qualifications' => $row['qualifications'],
            'bio' => strip_tags($row['bio']),
            'phone' => $row['phone'],
            'whatsapp' => $row['whatsapp'],
            'email' => $row['email'],
            'website' => $row['website']
        ];
    }
}

echo json_encode($teachers);
?>
