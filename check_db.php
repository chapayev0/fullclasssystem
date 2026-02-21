<?php
include 'db_connect.php';
$result = $conn->query("DESCRIBE classes");
if ($result) {
    echo "Table 'classes' structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>
