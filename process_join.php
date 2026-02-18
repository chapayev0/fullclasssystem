<?php
include 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get raw input data
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate inputs
    $name = isset($data['name']) ? trim($data['name']) : '';
    $class_preference = isset($data['class_preference']) ? trim($data['class_preference']) : '';
    $contact_number = isset($data['contact_number']) ? trim($data['contact_number']) : '';
    $whatsapp_available = isset($data['whatsapp_available']) && $data['whatsapp_available'] ? 1 : 0;
    $message = isset($data['message']) ? trim($data['message']) : '';

    $errors = [];
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($class_preference)) $errors[] = "Class preference is required.";
    if (empty($contact_number)) $errors[] = "Contact number is required.";

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO class_inquiries (name, class_preference, contact_number, whatsapp_available, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $name, $class_preference, $contact_number, $whatsapp_available, $message);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thank you! Your inquiry has been sent successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
