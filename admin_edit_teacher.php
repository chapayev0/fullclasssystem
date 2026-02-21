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

if (!isset($_GET['id'])) {
    header("Location: admin_teachers.php");
    exit();
}

$id = (int)$_GET['id'];
$result = $conn->query("SELECT * FROM teachers WHERE id = $id");
$teacher = $result->fetch_assoc();

if (!$teacher) {
    header("Location: admin_teachers.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_teacher'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $qualifications = mysqli_real_escape_string($conn, $_POST['qualifications']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $whatsapp = mysqli_real_escape_string($conn, $_POST['whatsapp']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $image_type = $_POST['image_type'];
    $image = $teacher['image'];

    if ($image_type === 'url') {
        if (!empty($_POST['image_url'])) {
            $image = mysqli_real_escape_string($conn, $_POST['image_url']);
        }
    } else {
        if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] == 0) {
            $target_dir = "uploads/teachers/";
            $file_extension = pathinfo($_FILES["teacher_image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid('teacher_') . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["teacher_image"]["tmp_name"], $target_file)) {
                // Delete old local file if it exists
                if (strpos($teacher['image'], 'uploads/teachers/') === 0 && file_exists($teacher['image'])) {
                    unlink($teacher['image']);
                }
                $image = $target_file;
            } else {
                $error_msg = "Error uploading image.";
            }
        }
    }

    // Account Update (Password)
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error_msg = "New passwords do not match!";
        }
    }

    if (empty($error_msg)) {
        $conn->begin_transaction();
        try {
            // Update User details first
            $update_user_sql = "UPDATE users SET email = ?, role = 'teacher' WHERE id = ?";
            $stmt_u = $conn->prepare($update_user_sql);
            $stmt_u->bind_param("si", $email, $teacher['user_id']);
            $stmt_u->execute();

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$hashed' WHERE id = {$teacher['user_id']}");
            }

            // Update Teacher details
            $stmt = $conn->prepare("UPDATE teachers SET name = ?, image = ?, qualifications = ?, phone = ?, whatsapp = ?, website = ?, email = ?, bio = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssssssssi", $name, $image, $qualifications, $phone, $whatsapp, $website, $email, $bio, $status, $id);
            
            if ($stmt->execute()) {
                $conn->commit();
                $success_msg = "Teacher updated successfully!";
                // Refresh teacher data
                $result = $conn->query("SELECT * FROM teachers WHERE id = $id");
                $teacher = $result->fetch_assoc();
            } else {
                throw new Exception("Error updating teacher: " . $stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher | Admin</title>
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
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 600px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box;}
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .hidden { display: none; }
        .preview-img { width: 100px; height: 100px; border-radius: 12px; object-fit: cover; margin-bottom: 1rem; border: 2px solid #e2e8f0; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header" style="margin-bottom: 2rem;">
            <a href="admin_teachers.php" style="text-decoration:none; color:var(--primary); font-weight:600;">← Back to Teachers</a>
            <h1 style="margin-top: 1rem;">Edit Teacher</h1>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div style="text-align: center;">
                    <img src="<?php echo htmlspecialchars($teacher['image']); ?>" class="preview-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($teacher['name']); ?>&background=random'">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Qualifications</label>
                    <input type="text" name="qualifications" class="form-control" value="<?php echo htmlspecialchars($teacher['qualifications']); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp Number</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?php echo htmlspecialchars($teacher['whatsapp']); ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($teacher['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website URL</label>
                        <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($teacher['website']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-control" rows="3"><?php echo htmlspecialchars($teacher['bio']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $teacher['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $teacher['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Update Image Mode</label>
                    <select name="image_type" class="form-control" onchange="toggleImageInput(this.value)">
                        <option value="keep">Keep Current</option>
                        <option value="upload">Upload New</option>
                        <option value="url">New URL</option>
                    </select>
                </div>
                
                <div class="form-group hidden" id="upload-group">
                    <label class="form-label">Upload New Image</label>
                    <input type="file" name="teacher_image" class="form-control" accept="image/*">
                </div>
                
                <div style="border-top: 1px solid #eee; margin-top: 2rem; padding-top: 1.5rem;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--dark);">Reset Login Password (Optional)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Retype new password">
                        </div>
                    </div>
                </div>
                
                <div class="form-group hidden" id="url-group">
                    <label class="form-label">New Image URL</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                </div>
                
                <button type="submit" name="edit_teacher" class="btn" style="width: 100%; margin-top: 1rem;">Save Changes</button>
            </form>
        </div>
    </div>
    
    <script>
        function toggleImageInput(value) {
            document.getElementById('upload-group').classList.add('hidden');
            document.getElementById('url-group').classList.add('hidden');
            
            if (value === 'upload') {
                document.getElementById('upload-group').classList.remove('hidden');
            } else if (value === 'url') {
                document.getElementById('url-group').classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
