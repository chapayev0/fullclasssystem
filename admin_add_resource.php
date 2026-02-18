<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$message = '';

// detect grade directories from classes/
$classes_dir = __DIR__ . '/classes';
$grades = [];
if (is_dir($classes_dir)) {
    $items = scandir($classes_dir);
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        if (is_dir($classes_dir . '/' . $it)) $grades[] = $it;
    }
}
array_unshift($grades, 'All');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = $_POST['grade'] ?? '';
    $category = $_POST['category'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $lesson_number = $_POST['lesson_number'] ?? '';
    
    // Video specific
    $video_type = 'none';
    $allow_download = isset($_POST['allow_download']) ? 1 : 0;
    
    if ($category === 'video') {
        $video_type = $_POST['video_source'] ?? 'file';
    }

    if (empty($grade) || empty($category) || empty($title)) {
        $message = 'Please fill all required fields.';
    } else {
        $success = false;
        $filename = '';
        $filepath = '';

        if (($category === 'video' && $video_type === 'link') || $category === 'link_library') {
            // Handle Video Link OR Link Library
            $link = trim($_POST['video_link'] ?? '');
            if (empty($link)) {
                $message = "Please enter the link URL.";
            } else {
                $filename = 'External Link';
                $filepath = $link;
                $success = true;
            }
        } else {
            // Handle File Upload
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $message = 'Please choose a file.';
            } else {
                $file = $_FILES['file'];
                
                // Ensure destination dir exists: classes/<grade>/<category>
                $dest_dir = str_replace('\\', '/', __DIR__ . '/classes/' . $grade . '/' . $category);
                if (!file_exists($dest_dir)) {
                    if (!mkdir($dest_dir, 0777, true)) {
                        $message = 'Failed to create destination directory: ' . $dest_dir;
                    }
                }
                
                if (empty($message)) { // No directory error
                    // sanitize filename
                    $safe_name = preg_replace('/[^A-Za-z0-9_\.-]/', '_', basename($file['name']));
                    $target_path = $dest_dir . '/' . $safe_name;
                    
                    if (file_exists($target_path)) {
                        $safe_name = time() . '_' . $safe_name;
                        $target_path = $dest_dir . '/' . $safe_name;
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $filename = $safe_name;
                        $filepath = 'classes/' . $grade . '/' . $category . '/' . $safe_name;
                        $success = true;
                    } else {
                        $message = 'Failed to move uploaded file.';
                    }
                }
            }
        }

        if ($success) {
            $stmt = $conn->prepare('INSERT INTO resources (lesson_number, title, description, filename, filepath, category, grade, uploaded_by, video_type, allow_download) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssssssi', $lesson_number, $title, $description, $filename, $filepath, $category, $grade, $_SESSION['user_id'], $video_type, $allow_download);
            
            if ($stmt->execute()) {
                $message = 'Resource uploaded successfully.';
            } else {
                $message = 'DB error: ' . $conn->error;
                if ($category !== 'video' || $video_type === 'file') {
                    // rollback file if it was an upload
                    @unlink(__DIR__ . '/' . $filepath);
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Resource | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Outfit',sans-serif;background:#f8fafc;margin:0;padding:20px}
        .container{max-width:900px;margin:0 auto}
        .card{background:#fff;padding:20px;border-radius:10px;box-shadow:0 4px 12px rgba(15,23,42,0.06)}
        .row{display:flex;gap:12px}
        .col{flex:1}
        label{display:block;margin-bottom:6px;font-weight:600}
        input[type=text],select,textarea{width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px;box-sizing:border-box;}
        .btn{background:#0ea5e9;color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer}
        .msg{margin:10px 0;padding:10px;border-radius:8px}
        .msg.success{background:#ecfccb;color:#15803d}
        .msg.error{background:#fee2e2;color:#b91c1c}
        .hidden { display: none; }
        .radio-group { display: flex; gap: 15px; margin-bottom: 10px; }
        .checkbox-group { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Resource</h1>
        <div class="card">
            <?php if($message): ?>
                <div class="msg <?php echo (strpos($message,'successfully')!==false ? 'success' : 'error'); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="row" style="margin-bottom:12px">
                    <div class="col">
                        <label for="grade">Class / Grade</label>
                        <select id="grade" name="grade" required>
                            <option value="">-- Select Grade --</option>
                            <?php foreach($grades as $g): ?>
                                <option value="<?php echo htmlspecialchars($g); ?>"><?php echo htmlspecialchars($g); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label for="category">Category</label>
                        <select id="category" name="category" required onchange="toggleFields()">
                            <option value="">-- Select Category --</option>
                            <option value="theory">Theory</option>
                            <option value="paper">Paper</option>
                            <option value="tute">Tute</option>
                            <option value="materials">Materials</option>
                            <option value="video">Video</option>
                            <option value="link_library">Link Library</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:12px">
                    <label for="lesson_number">Lesson Number</label>
                    <input type="text" id="lesson_number" name="lesson_number" placeholder="e.g. Lesson 1" required>
                </div>

                <div style="margin-bottom:12px">
                    <label for="title">Title / Name</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div style="margin-bottom:12px">
                    <label for="description">Small Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>

                <!-- Video Specific Fields -->
                <div id="video_options" class="hidden" style="margin-bottom:12px; padding:15px; background:#f0f9ff; border-radius:8px;">
                    <label>Video Source</label>
                    <div class="radio-group">
                        <label><input type="radio" name="video_source" value="file" checked onclick="toggleVideoSource()"> Upload Video File</label>
                        <label><input type="radio" name="video_source" value="link" onclick="toggleVideoSource()"> External Link (YouTube/URL)</label>
                    </div>



                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="allow_download" value="1" checked>
                            Allow Download
                        </label>
                        <small style="color:#64748b; display:block; margin-top:2px;">If unchecked, download button will be hidden.</small>
                    </div>
                </div>

                <div id="video_link_input" class="hidden" style="margin-bottom:12px">
                    <label for="video_link">External URL</label>
                    <input type="text" id="video_link" name="video_link" placeholder="https://youtube.com/...">
                </div>

                <div id="file_input" style="margin-bottom:12px">
                    <label for="file">Choose File</label>
                    <input type="file" id="file" name="file">
                </div>

                <div style="display:flex;gap:10px; margin-top:20px;">
                    <button type="submit" class="btn">Upload / Save</button>
                    <a href="admin_dashboard.php" style="align-self:center;text-decoration:none;color:#64748b">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleFields() {
            const category = document.getElementById('category').value;
            const videoOptions = document.getElementById('video_options');
            const fileInput = document.getElementById('file_input');
            const videoLinkInput = document.getElementById('video_link_input');
            const fileField = document.getElementById('file');
            
            // Reset visibility
            videoOptions.classList.add('hidden');
            videoLinkInput.classList.add('hidden');
            fileInput.classList.add('hidden');
            fileField.required = false;

            if (category === 'video') {
                videoOptions.classList.remove('hidden');
                toggleVideoSource(); // Determines if file or link input is shown
            } else if (category === 'link_library') {
                // Link Library only needs the URL input
                videoLinkInput.classList.remove('hidden');
                document.getElementById('video_link').placeholder = "Enter External Link URL";
            } else {
                // Standard File Upload
                fileInput.classList.remove('hidden');
                fileField.required = true;
            }
        }

        function toggleVideoSource() {
            // Only relevant if category is 'video'
            if (document.getElementById('category').value !== 'video') return;

            const source = document.querySelector('input[name="video_source"]:checked').value;
            const linkInput = document.getElementById('video_link_input');
            const fileInput = document.getElementById('file_input');
            const fileField = document.getElementById('file');
            const linkField = document.getElementById('video_link');

            if (source === 'link') {
                linkInput.classList.remove('hidden');
                linkField.placeholder = "https://youtube.com/...";
                fileInput.classList.add('hidden');
                fileField.required = false;
            } else {
                linkInput.classList.add('hidden');
                fileInput.classList.remove('hidden');
                fileField.required = true;
            }
        }
    </script>
</body>
</html>
