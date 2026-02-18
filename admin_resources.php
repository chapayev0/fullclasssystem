<?php
session_start();
include 'db_connect.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Delete Resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    $resource_id = $_POST['resource_id'];
    
    // Get file path first
    $stmt = $conn->prepare("SELECT filepath FROM resources WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $file_data = $res->fetch_assoc();
    $stmt->close();
    
    if ($file_data) {
        $filepath = __DIR__ . '/' . $file_data['filepath'];
        
        // Delete from DB
        $stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
        $stmt->bind_param("i", $resource_id); // Fixed variable name here
        if ($stmt->execute()) {
            // Delete file
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            $success_msg = "Resource deleted successfully!";
        } else {
            $error_msg = "Error deleting resource: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Add Resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    // Handle Multi-Select Grade
    $grade_input = $_POST['grade'] ?? [];
    if (empty($grade_input)) {
        $grade = '';
    } elseif (in_array('All', $grade_input)) {
        $grade = 'All';
    } else {
        $grade = implode(',', $grade_input);
    }

    $category = $_POST['category'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $lesson_number = $_POST['lesson_number'];
    
    // Video specific
    $video_type = 'none';
    $allow_download = isset($_POST['allow_download']) ? 1 : 0;
    
    if ($category === 'video') {
        $video_type = $_POST['video_source'] ?? 'file';
    }

    if (empty($grade) || empty($category) || empty($title)) {
        $error_msg = 'Please fill all required fields.';
    } else {
        $success = false;
        $filename = '';
        $filepath = '';

        if (($category === 'video' && $video_type === 'link') || $category === 'link_library') {
            // Handle Video Link OR Link Library
            $link = trim($_POST['video_link'] ?? '');
            if (empty($link)) {
                $error_msg = "Please enter the link URL.";
            } else {
                $filename = 'External Link';
                $filepath = $link;
                $success = true;
            }
        } else {
            // Handle File Upload
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $error_msg = 'Please choose a file.';
            } else {
                $file = $_FILES['file'];
                // Destination
                $dest_dir = __DIR__ . '/classes/' . $grade . '/' . $category;
                if (!is_dir($dest_dir)) {
                    mkdir($dest_dir, 0755, true);
                }
                
                $safe_name = preg_replace('/[^A-Za-z0-9_\.-]/', '_', basename($file['name']));
                $target_path = $dest_dir . '/' . $safe_name;
                
                // Unique name
                if (file_exists($target_path)) {
                    $safe_name = time() . '_' . $safe_name;
                    $target_path = $dest_dir . '/' . $safe_name;
                }
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $filename = $safe_name;
                    $filepath = 'classes/' . $grade . '/' . $category . '/' . $safe_name;
                    $success = true;
                } else {
                    $error_msg = "Failed to move uploaded file.";
                }
            }
        }

        if ($success) {
            $stmt = $conn->prepare("INSERT INTO resources (lesson_number, title, description, filename, filepath, category, grade, uploaded_by, video_type, allow_download) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssi", $lesson_number, $title, $description, $filename, $filepath, $category, $grade, $_SESSION['user_id'], $video_type, $allow_download);
            
            if ($stmt->execute()) {
                $success_msg = "Resource uploaded successfully!";
            } else {
                $error_msg = "DB Error: " . $conn->error;
                if ($category !== 'video' || $video_type === 'file') {
                    @unlink(__DIR__ . '/' . $filepath);
                }
            }
            $stmt->close();
        }
    }
}

// Fetch all resources
$resources = [];
$result = $conn->query("SELECT * FROM resources ORDER BY grade ASC, category ASC, id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --danger: #EF4444;
            --success: #10B981;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            margin: 0;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background: var(--dark);
            color: white;
            min-height: 100vh;
            padding: 2rem;
            position: fixed;
            left: 0;
            top: 0;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 3rem;
            color: var(--primary);
        }
        .nav-link {
            display: block;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 1rem 0;
            transition: color 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            font-weight: 600;
        }
        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 290px;
        }
        .header {
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .tab-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            transition: all 0.3s;
        }
        .tab-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Form */
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-danger { background: var(--danger); }
        .btn-edit { background: var(--secondary); margin-right: 0.5rem; }
        
        /* Table */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        th { color: var(--gray); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Resources</h1>
            <p style="color: var(--gray);">Upload and manage class materials.</p>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('view')">View Resources</button>
            <button class="tab-btn" onclick="switchTab('add')">Add New Resource</button>
        </div>
        
        <!-- View Tab -->
        <div id="view-tab">
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Grade & Loc</th>
                                <th>Resource Info</th>
                                <th>File</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resources)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--gray);">No resources found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($resources as $res): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700;">Grade <?php echo htmlspecialchars($res['grade']); ?></div>
                                            <div style="font-size:0.9rem; color:var(--gray); text-transform:capitalize;"><?php echo htmlspecialchars($res['category']); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;"><?php echo htmlspecialchars($res['title']); ?></div>
                                            <div style="font-size:0.9rem; color: #64748B;"><?php echo htmlspecialchars($res['lesson_number']); ?></div>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($res['filepath']); ?>" target="_blank" style="color:var(--primary); text-decoration:none;">View File</a>
                                        </td>
                                        <td>
                                            <div style="display:flex;">
                                                <a href="admin_edit_resource.php?id=<?php echo $res['id']; ?>" class="btn btn-edit">Edit</a>
                                                <form method="POST" onsubmit="return confirm('Delete this resource permanently?');">
                                                    <input type="hidden" name="resource_id" value="<?php echo $res['id']; ?>">
                                                    <button type="submit" name="delete_resource" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add Tab -->
        <div id="add-tab" class="hidden">
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                    <div class="form-group">
                        <label class="form-label">Grade (Select all that apply)</label>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <label style="cursor:pointer;">
                                <input type="checkbox" name="grade[]" value="All"> All Classes
                            </label>
                            <?php for($i=6; $i<=11; $i++): ?>
                                <label style="cursor:pointer;">
                                    <input type="checkbox" name="grade[]" value="<?php echo $i; ?>"> Grade <?php echo $i; ?>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select id="category" name="category" class="form-control" required onchange="toggleFields()">
                            <option value="">Select Category</option>
                            <option value="theory">Theory</option>
                            <option value="paper">Paper</option>
                            <option value="tute">Tute</option>
                            <option value="materials">Materials</option>
                            <option value="video">Video</option>
                            <option value="link_library">Link Library</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Lesson Number</label>
                        <input type="text" name="lesson_number" class="form-control" placeholder="e.g. Lesson 1" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Resource Title" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <!-- Video Specific Fields -->
                    <div id="video_options" class="hidden" style="margin-bottom:1.5rem; padding:15px; background:#f0f9ff; border-radius:8px;">
                        <label class="form-label">Video Source</label>
                        <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                            <label><input type="radio" name="video_source" value="file" checked onclick="toggleVideoSource()"> Upload Video File</label>
                            <label><input type="radio" name="video_source" value="link" onclick="toggleVideoSource()"> External Link (YouTube/URL)</label>
                        </div>



                        <div style="margin-top: 10px;">
                            <label>
                                <input type="checkbox" name="allow_download" value="1" checked>
                                Allow Download
                            </label>
                            <small style="color:#64748b; display:block; margin-top:2px;">If unchecked, download button will be hidden.</small>
                        </div>


                    </div>

                    <div id="video_link_input" class="hidden" style="margin-bottom:1.5rem">
                        <label class="form-label">External URL</label>
                        <input type="text" id="video_link" name="video_link" class="form-control" placeholder="https://...">
                    </div>

                    <div id="file_input" class="form-group">
                        <label class="form-label">File</label>
                        <input type="file" id="file" name="file" class="form-control">
                    </div>
                    
                    <button type="submit" name="add_resource" class="btn">Upload Resource</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            document.getElementById('view-tab').classList.add('hidden');
            document.getElementById('add-tab').classList.add('hidden');
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        function toggleFields() {
            const category = document.getElementById('category').value;
            const videoOptions = document.getElementById('video_options');
            const fileInput = document.getElementById('file_input');
            const videoLinkInput = document.getElementById('video_link_input');
            const fileField = document.getElementById('file');
            
            // Reset
            videoOptions.classList.add('hidden');
            videoLinkInput.classList.add('hidden');
            fileInput.classList.add('hidden');
            fileField.required = false;
            
            if (category === 'video') {
                videoOptions.classList.remove('hidden');
                toggleVideoSource(); 
            } else if (category === 'link_library') {
                // Show only URL input, reuse the video_link_input div but ensure it's visible
                videoLinkInput.classList.remove('hidden');
                document.getElementById('video_link').placeholder = "Enter External Link URL";
            } else {
                // Standard File
                fileInput.classList.remove('hidden');
                fileField.required = true;
            }
        }

        function toggleVideoSource() {
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
