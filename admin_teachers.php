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

// Handle Delete Teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_teacher'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    
    // Get image path to delete file if it's local
    $stmt = $conn->prepare("SELECT image FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $image_path = $row['image'];
        if (strpos($image_path, 'uploads/teachers/') === 0 && file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    if ($conn->query("DELETE FROM teachers WHERE id = $teacher_id")) {
        $success_msg = "Teacher deleted successfully!";
    } else {
        $error_msg = "Error deleting teacher: " . $conn->error;
    }
}

// Handle Add Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_teacher'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $qualifications = mysqli_real_escape_string($conn, $_POST['qualifications']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $whatsapp = mysqli_real_escape_string($conn, $_POST['whatsapp']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $image_type = $_POST['image_type']; // 'upload' or 'url'
    $image = '';

    if ($image_type === 'url') {
        $image = mysqli_real_escape_string($conn, $_POST['image_url']);
    } else {
        if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] == 0) {
            $target_dir = "uploads/teachers/";
            $file_extension = pathinfo($_FILES["teacher_image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid('teacher_') . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["teacher_image"]["tmp_name"], $target_file)) {
                $image = $target_file;
            } else {
                $error_msg = "Error uploading image.";
            }
        }
    }

    if (empty($error_msg)) {
        $stmt = $conn->prepare("INSERT INTO teachers (name, image, qualifications, phone, whatsapp, website, email, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $image, $qualifications, $phone, $whatsapp, $website, $email, $bio);
        
        if ($stmt->execute()) {
            $success_msg = "Teacher added successfully!";
        } else {
            $error_msg = "Error adding teacher: " . $stmt->error;
        }
    }
}

// Fetch Teachers
$teachers = [];
$result = $conn->query("SELECT * FROM teachers ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers | Admin</title>
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
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .header { margin-bottom: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .tab-btn { background: white; border: 1px solid #e2e8f0; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; color: var(--gray); transition: all 0.3s; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box;}
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-danger { background: var(--danger); }
        .btn-edit { background: var(--secondary); margin-right: 0.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        th { color: var(--gray); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .hidden { display: none; }
        .teacher-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; background: #e2e8f0; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Teachers</h1>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('view')">View Teachers</button>
            <button class="tab-btn" onclick="switchTab('add')">Add Teacher</button>
        </div>
        
        <!-- View Tab -->
        <div id="view-tab">
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Qualifications</th>
                            <th>Contact Info</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--gray);">No teachers found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $t): ?>
                                <tr>
                                    <td><img src="<?php echo htmlspecialchars($t['image']); ?>" class="teacher-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($t['name']); ?>&background=random'"></td>
                                    <td><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t['qualifications']); ?></td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: var(--gray);">
                                            <?php if($t['phone']): ?><div>📞 <?php echo htmlspecialchars($t['phone']); ?></div><?php endif; ?>
                                            <?php if($t['whatsapp']): ?><div>💬 <?php echo htmlspecialchars($t['whatsapp']); ?></div><?php endif; ?>
                                            <?php if($t['email']): ?><div>📧 <?php echo htmlspecialchars($t['email']); ?></div><?php endif; ?>
                                            <?php if($t['website']): ?><div>🌐 <a href="<?php echo htmlspecialchars($t['website']); ?>" target="_blank" style="color: var(--primary);">Website</a></div><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex;">
                                            <a href="admin_edit_teacher.php?id=<?php echo $t['id']; ?>" class="btn btn-edit">Edit</a>
                                            <form method="POST" onsubmit="return confirm('Confirm deletion?');">
                                                <input type="hidden" name="teacher_id" value="<?php echo $t['id']; ?>">
                                                <button type="submit" name="delete_teacher" class="btn btn-danger">Delete</button>
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
        
        <!-- Add Tab -->
        <div id="add-tab" class="hidden">
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qualifications</label>
                        <input type="text" name="qualifications" class="form-control" placeholder="e.g. BSc in IT (Hons), 5+ Years Exp" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 0777 695 130">
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" name="whatsapp" class="form-control" placeholder="e.g. 0777 695 130">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. teacher@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Website URL</label>
                            <input type="url" name="website" class="form-control" placeholder="e.g. https://example.com">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bio (Briefly)</label>
                        <textarea name="bio" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image Input Mode</label>
                        <select name="image_type" class="form-control" onchange="toggleImageInput(this.value)">
                            <option value="upload">Upload Image</option>
                            <option value="url">Image URL</option>
                        </select>
                    </div>
                    <div class="form-group" id="upload-group">
                        <label class="form-label">Upload Image</label>
                        <input type="file" name="teacher_image" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group hidden" id="url-group">
                        <label class="form-label">Image URL</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                    </div>
                    <button type="submit" name="add_teacher" class="btn" style="width: 100%; margin-top: 1rem;">Add Teacher</button>
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

        function toggleImageInput(value) {
            if (value === 'upload') {
                document.getElementById('upload-group').classList.remove('hidden');
                document.getElementById('url-group').classList.add('hidden');
            } else {
                document.getElementById('upload-group').classList.add('hidden');
                document.getElementById('url-group').classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
