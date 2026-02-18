<?php
include_once 'env_loader.php';

try {
    EnvLoader::load(__DIR__ . '/.env');
} catch (Exception $e) {
    die($e->getMessage()); // Or handle gracefully
}

$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
