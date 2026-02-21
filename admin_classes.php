<?php
session_start();
include 'db_connect.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Add Class
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $grade = $_POST['grade'];
    $subject = $_POST['subject'];
    $institute_phone = $_POST['institute_phone'];
    $class_day = $_POST['class_day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $description = $_POST['description'];
    
    // Handle Logo
    $class_logo = '';
    
    // Check for file upload first
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assest/images/class_logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid('class_logo_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $upload_path)) {
                $class_logo = $upload_path;
            } else {
                $error_msg = "Failed to upload image.";
            }
        } else {
            $error_msg = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
        }
    } 
    // If no file, check for URL
    elseif (!empty($_POST['logo_url'])) {
        $class_logo = $_POST['logo_url'];
    }
    if (empty($error_msg)) {
        $logo_emoji = '🏫'; 

        $stmt = $conn->prepare("INSERT INTO classes (grade, subject, institute_phone, class_day, start_time, end_time, description, logo_emoji, class_logo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss", $grade, $subject, $institute_phone, $class_day, $start_time, $end_time, $description, $logo_emoji, $class_logo);

        if ($stmt->execute()) {
            $success_msg = "Class added successfully!";
        } else {
            $error_msg = "Error adding class: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Delete Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
    $class_id = $_POST['class_id'];
    $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    if ($stmt->execute()) {
        $success_msg = "Class deleted successfully!";
    } else {
        $error_msg = "Error deleting class: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all classes
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY grade ASC, created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Fetch Subjects for dropdown
$subjects_list = [];
$s_result = $conn->query("SELECT name FROM subjects ORDER BY name ASC");
if ($s_result) {
    while ($s_row = $s_result->fetch_assoc()) {
        $subjects_list[] = $s_row['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes | ICT with Dilhara Admin</title>
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
            margin-left: 290px; /* sidebar width + padding */
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
        
        /* Tabs */
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
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
        }
        .btn {
            background: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-danger {
            background: var(--danger);
        }
        .btn-edit {
            background: var(--secondary);
            margin-right: 0.5rem;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            color: var(--gray);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
        }
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Classes</h1>
            <p style="color: var(--gray);">Add, edit, or delete class schedules.</p>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('view')">View Classes</button>
            <button class="tab-btn" onclick="switchTab('add')">Add New Class</button>
        </div>
        
        <!-- View Classes Tab -->
        <div id="view-tab">
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Grade & Image</th>
                                <th>Day & Time</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--gray);">No classes found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight: 700; color: var(--dark);">Grade <?php echo $class['grade']; ?></span>
                                            <div style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.3rem;"><?php echo htmlspecialchars($class['subject']); ?></div>
                                            <div style="width: 60px; height: 60px; border-radius: 12px; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; margin-top: 0.2rem;">
                                                <?php if (!empty($class['class_logo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($class['class_logo']); ?>" alt="Class Image" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <span style="font-size: 1.5rem;"><?php echo !empty($class['logo_emoji']) ? htmlspecialchars($class['logo_emoji']) : '🏫'; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($class['class_day']); ?></div>
                                            <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($class['start_time']) . ' - ' . htmlspecialchars($class['end_time']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($class['institute_phone']); ?></td>
                                        <td>
                                            <div style="display: flex;">
                                                <a href="admin_edit_class.php?id=<?php echo $class['id']; ?>" class="btn btn-edit">Edit</a>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                                    <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                                    <button type="submit" name="delete_class" class="btn btn-danger">Delete</button>
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
        
        <!-- Add Class Tab -->
        <div id="add-tab" class="hidden">
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: flex; gap: 2rem;">
                        <div style="flex: 1;">
                            <div class="form-group">
                                <label class="form-label">Grade</label>
                                <select name="grade" class="form-control" required>
                                    <option value="">Select Grade</option>
                                    <option value="6">Grade 6</option>
                                    <option value="7">Grade 7</option>
                                    <option value="8">Grade 8</option>
                                    <option value="9">Grade 9</option>
                                    <option value="10">Grade 10 (O/L)</option>
                                    <option value="11">Grade 11 (A/L)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Class Subject</label>
                                <select name="subject" class="form-control" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects_list as $s_name): ?>
                                        <option value="<?php echo htmlspecialchars($s_name); ?>"><?php echo htmlspecialchars($s_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contact Phone Number</label>
                                <input type="text" name="institute_phone" class="form-control" placeholder="e.g. +94 77 123 4567" required>
                            </div>
                        </div>
                        
                        <div style="flex: 1;">
                            <div class="form-group">
                                <label class="form-label">Class Day</label>
                                <select name="class_day" class="form-control" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Start Time</label>
                                    <input type="text" name="start_time" class="form-control" placeholder="e.g. 8:00 AM" required>
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">End Time</label>
                                    <input type="text" name="end_time" class="form-control" placeholder="e.g. 10:00 AM" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Class Image</label>
                                <div style="margin-bottom: 0.5rem;">
                                    <input type="text" name="logo_url" class="form-control" placeholder="Image URL (e.g. https://placehold.co/100)">
                                </div>
                                <div style="text-align: center; margin: 0.5rem 0; color: var(--gray); font-size: 0.9rem; font-weight: bold;">- OR -</div>
                                <div>
                                    <input type="file" name="logo_upload" class="form-control" accept="image/*">
                                </div>
                                <small style="color: var(--gray); display: block; margin-top: 0.5rem;">Upload an image or paste a link. (Preferred size: Square)</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Short description of the class..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_class" class="btn">Add Class</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // content
            document.getElementById('view-tab').classList.add('hidden');
            document.getElementById('add-tab').classList.add('hidden');
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            // buttons
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
