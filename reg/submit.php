<?php
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $batch = isset($_POST['batch']) ? trim($_POST['batch']) : '';
    $experience = isset($_POST['experience']) ? trim($_POST['experience']) : '';
    $background = isset($_POST['background']) ? trim($_POST['background']) : '';
    $goals = isset($_POST['goals']) ? trim($_POST['goals']) : '';

    $errors = [];
    if (empty($firstName) || empty($lastName)) $errors[] = "Name is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($batch)) $errors[] = "Schedule preference is required.";

    if (!empty($errors)) {
        // Handle errors - for now, just die or redirect with error
        echo "Errors: " . implode(', ', $errors);
        exit;
    }

    // Combine fields
    $name = $firstName . ' ' . $lastName;
    
    // Construct a comprehensive message
    $message = "Email: $email\n";
    $message .= "Experience Level: $experience\n";
    $message .= "Background: $background\n";
    $message .= "Goals: $goals";

    // Default values
    $whatsapp_available = 1; // Assuming yes for now, or could check if they have it
    // Or we could map it if we had a checkbox, but the form doesn't have one explicitly for WA
    
    // Mapping
    $class_preference = $batch;
    $contact_number = $phone;

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO class_inquiries (name, class_preference, contact_number, whatsapp_available, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $name, $class_preference, $contact_number, $whatsapp_available, $message);

    if ($stmt->execute()) {
        // Success
        header("Location: index.html?status=success#register");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    // Not a POST request
    header("Location: index.html");
    exit();
}
?>
