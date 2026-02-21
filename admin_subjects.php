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

// Handle Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $name = $_POST['name'];
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    $description = $_POST['description'];
    
    // Handle Logo
    $subject_logo = '';
    
    // Check for file upload first
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assest/images/subject_logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid('subject_logo_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $upload_path)) {
                $subject_logo = $upload_path;
            } else {
                $error_msg = "Failed to upload image.";
            }
        } else {
            $error_msg = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
        }
    } 
    // If no file, check for URL
    elseif (!empty($_POST['logo_url'])) {
        $subject_logo = $_POST['logo_url'];
    }

    if (empty($error_msg)) {
        $logo_emoji = '📚'; 

        $stmt = $conn->prepare("INSERT INTO subjects (name, teacher_id, description, logo_emoji, subject_logo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $name, $teacher_id, $description, $logo_emoji, $subject_logo);

        if ($stmt->execute()) {
            $success_msg = "Subject added successfully!";
        } else {
            $error_msg = "Error adding subject: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Delete Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $subject_id = $_POST['subject_id'];
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    if ($stmt->execute()) {
        $success_msg = "Subject deleted successfully!";
    } else {
        $error_msg = "Error deleting subject: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all subjects with teacher names
$subjects = [];
$result = $conn->query("SELECT s.*, t.name as teacher_name FROM subjects s LEFT JOIN teachers t ON s.teacher_id = t.id ORDER BY s.name ASC, s.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Fetch all active teachers
$teachers_list = [];
$t_result = $conn->query("SELECT id, name FROM teachers WHERE status = 'active' ORDER BY name ASC");
if ($t_result) {
    while ($t_row = $t_result->fetch_assoc()) {
        $teachers_list[] = $t_row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects | Admin Dashboard</title>
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
        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 250px; /* sidebar width */
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
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Subjects</h1>
            <p style="color: var(--gray);">Create and manage academic subjects.</p>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('view')">View Subjects</button>
            <button class="tab-btn" onclick="switchTab('add')">Add New Subject</button>
        </div>
        
        <!-- View Subjects Tab -->
        <div id="view-tab">
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject & Image</th>
                                <th>Teacher</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subjects)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--gray);">No subjects found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight: 700; color: var(--dark);"><?php echo htmlspecialchars($subject['name']); ?></span>
                                            <div style="width: 64px; height: 64px; border-radius: 12px; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; margin-top: 0.5rem;">
                                                <?php if (!empty($subject['subject_logo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($subject['subject_logo']); ?>" alt="Subject Image" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <span style="font-size: 1.5rem;"><?php echo htmlspecialchars($subject['logo_emoji']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; color: var(--dark);">
                                                <?php echo !empty($subject['teacher_name']) ? htmlspecialchars($subject['teacher_name']) : '<span style="color: var(--gray); font-style: italic;">Not Assigned</span>'; ?>
                                            </div>
                                        </td>
                                        <td style="color: var(--gray); font-size: 0.9rem; max-width: 300px;"><?php echo htmlspecialchars(mb_strimwidth($subject['description'], 0, 100, "...")); ?></td>
                                        <td>
                                            <div style="display: flex;">
                                                <a href="admin_edit_subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-edit">Edit</a>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                    <button type="submit" name="delete_subject" class="btn btn-danger">Delete</button>
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
        
        <!-- Add Subject Tab -->
        <div id="add-tab" class="hidden">
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label">Subject Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Mathematics, ICT, Science" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Assign Teacher</label>
                            <select name="teacher_id" class="form-control">
                                <option value="">-- No Teacher --</option>
                                <?php foreach ($teachers_list as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: var(--gray);">Choose a teacher from the active teachers list.</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Brief overview of the subject..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Subject Image</label>
                            <div style="margin-bottom: 0.5rem;">
                                <input type="text" name="logo_url" class="form-control" placeholder="Image URL (e.g. https://placehold.co/100)">
                            </div>
                            <div style="text-align: center; margin: 0.5rem 0; color: var(--gray); font-size: 0.9rem; font-weight: bold;">- OR -</div>
                            <div>
                                <input type="file" name="logo_upload" class="form-control" accept="image/*">
                            </div>
                            <small style="color: var(--gray);">Upload an image or paste a link. (Preferred size: Square)</small>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_subject" class="btn" style="margin-top: 1rem;">Add Subject</button>
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
            
            // Highlight current button
            if (event) {
                event.target.classList.add('active');
            }
        }
    </script>
</body>
</html>
