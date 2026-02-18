<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if file is uploaded
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $uploadDir = 'uploads/wall_images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Validate file type (Secure MIME check)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['file']['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    if (array_key_exists($mimeType, $allowedMimes)) {
        // Force extension to match mime type
        $ext = $allowedMimes[$mimeType];
        $fileName = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
    

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            echo json_encode(['location' => $targetPath]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to move uploaded file.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Detected: ' . $mimeType]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded.']);
}
?>
