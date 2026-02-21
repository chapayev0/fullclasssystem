<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch teacher data
$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    die("Teacher profile not found. Please contact admin.");
}

$teacher_id = $teacher['id'];

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $qualifications = mysqli_real_escape_string($conn, $_POST['qualifications']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $whatsapp = mysqli_real_escape_string($conn, $_POST['whatsapp']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $image_type = $_POST['image_type'];
    $image = $teacher['image'];

    // Image Handling
    if ($image_type === 'url') {
        if (!empty($_POST['image_url'])) {
            $image = mysqli_real_escape_string($conn, $_POST['image_url']);
        }
    } elseif ($image_type === 'upload') {
        if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] == 0) {
            $target_dir = "uploads/teachers/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
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

    // Password Handling
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $password_update = false;

    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error_msg = "New passwords do not match!";
        } else {
            $password_update = true;
        }
    }

    if (empty($error_msg)) {
        $conn->begin_transaction();
        try {
            // Update User details
            $update_user_sql = "UPDATE users SET email = ? WHERE id = ?";
            $stmt_u = $conn->prepare($update_user_sql);
            $stmt_u->bind_param("si", $email, $user_id);
            $stmt_u->execute();

            if ($password_update) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$hashed' WHERE id = $user_id");
            }

            // Update Teacher details
            $stmt_t = $conn->prepare("UPDATE teachers SET name = ?, image = ?, qualifications = ?, phone = ?, whatsapp = ?, website = ?, email = ?, bio = ? WHERE id = ?");
            $stmt_t->bind_param("ssssssssi", $name, $image, $qualifications, $phone, $whatsapp, $website, $email, $bio, $teacher_id);
            
            if ($stmt_t->execute()) {
                $conn->commit();
                $success_msg = "Profile updated successfully!";
                // Refresh data
                $stmt->execute();
                $teacher = $stmt->get_result()->fetch_assoc();
            } else {
                throw new Exception("Error updating profile: " . $stmt_t->error);
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
    <title>My Profile | Teacher Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --success: #10B981;
            --danger: #EF4444;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; transition: margin-left 0.3s ease; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } }
        
        .card { background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 800px; margin: 0 auto; }
        .header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; color: var(--dark); font-weight: 800; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 0.8rem; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 1rem; transition: border-color 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        
        .btn-save { background: var(--primary); color: white; border: none; padding: 1rem 2rem; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: transform 0.2s; width: 100%; }
        .btn-save:hover { transform: translateY(-2px); }
        
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #DCFCE7; color: #166534; }
        .alert-danger { background: #FEE2E2; color: #991B1B; }
        
        .profile-img-container { text-align: center; margin-bottom: 2rem; }
        .profile-img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); margin-bottom: 1rem; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <?php include 'teacher_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="header">
                <h1>My Profile</h1>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="profile-img-container">
                    <img src="<?php echo htmlspecialchars($teacher['image']); ?>" class="profile-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($teacher['name']); ?>&background=random'">
                    <div class="form-group" style="max-width: 300px; margin: 0 auto;">
                        <select name="image_type" class="form-control" onchange="toggleImageInput(this.value)">
                            <option value="keep">Keep Current Image</option>
                            <option value="upload">Upload New Photo</option>
                            <option value="url">Use Image URL</option>
                        </select>
                    </div>
                </div>

                <div id="upload-group" class="form-group hidden">
                    <label class="form-label">Upload New Photo</label>
                    <input type="file" name="teacher_image" class="form-control" accept="image/*">
                </div>

                <div id="url-group" class="form-group hidden">
                    <label class="form-label">Image URL</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://example.com/photo.jpg">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qualifications</label>
                        <input type="text" name="qualifications" class="form-control" value="<?php echo htmlspecialchars($teacher['qualifications']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp Number</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?php echo htmlspecialchars($teacher['whatsapp']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($teacher['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($teacher['website']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Short Bio</label>
                    <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($teacher['bio']); ?></textarea>
                </div>

                <div style="margin: 2rem 0; padding-top: 2rem; border-top: 2px solid #f1f5f9;">
                    <h3 style="color: var(--dark); margin-bottom: 1.5rem;">Security: Change Password</h3>
                    <div class="form-grid">
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

                <button type="submit" name="update_profile" class="btn-save">Update Profile</button>
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
