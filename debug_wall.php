<?php
include 'db_connect.php';

// Fetch last post
$sql = "SELECT * FROM wall_posts ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    echo "<h1>Last Post Debug</h1>";
    echo "<h3>Title: " . htmlspecialchars($row['title']) . "</h3>";
    echo "<h3>Image Path (Thumbnail): " . htmlspecialchars($row['image_path']) . "</h3>";
    echo "<h3>Raw Content (HTML):</h3>";
    echo "<pre>" . htmlspecialchars($row['content']) . "</pre>";
    echo "<h3>Rendered Content:</h3>";
    echo "<div style='border:1px solid #ccc; padding:10px;'>" . $row['content'] . "</div>";
    
    // Check if thumbnail file exists
    if ($row['image_path']) {
        echo "<b>Thumbnail File Exists: </b>" . (file_exists($row['image_path']) ? 'Yes' : 'No') . "<br>";
    }
    
    // Extract images from content and check existence
    preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $row['content'], $matches);
    if (!empty($matches[1])) {
        echo "<h3>Images found in content:</h3>";
        foreach ($matches[1] as $imgSrc) {
            echo "Source: " . htmlspecialchars($imgSrc) . " - ";
            echo "File Exists on Server: " . (file_exists($imgSrc) ? 'Yes' : 'No') . "<br>";
        }
    } else {
        echo "<h3>No images found in content regex.</h3>";
    }

} else {
    echo "No posts found.";
}
?>
