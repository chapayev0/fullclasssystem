<?php
include 'db_connect.php';

// Fix corrupted posts where quotes are escaped like \"
$sql = "UPDATE wall_posts SET content = REPLACE(content, '\\\"', '\"')";
if ($conn->query($sql)) {
    echo "Fixed content quotes.<br>";
} else {
    echo "Error fixing quotes: " . $conn->error . "<br>";
}

// Fix possible double-escaped slashes in paths like uploads\/
$sql2 = "UPDATE wall_posts SET content = REPLACE(content, 'uploads\\\/', 'uploads/')";
if ($conn->query($sql2)) {
    echo "Fixed paths.<br>";
}

echo "Database cleanup complete.";
?>
