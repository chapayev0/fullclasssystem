<?php
include 'db_connect.php';

$sql = "ALTER TABLE classes ADD COLUMN subject VARCHAR(100) AFTER grade";

if ($conn->query($sql) === TRUE) {
    echo "Column 'subject' added successfully.";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>
