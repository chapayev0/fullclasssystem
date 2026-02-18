<?php
session_start();
include 'db_connect.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get class ID
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$class_id) {
    header("Location: admin_classes.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_class'])) {
    $grade = $_POST['grade'];
    $institute_name = $_POST['institute_name'];
    $institute_address = $_POST['institute_address'];
    $institute_phone = $_POST['institute_phone'];
    $class_day = $_POST['class_day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $description = $_POST['description'];
    // Handle Logo Update
    $class_logo = $_POST['current_logo']; // Default to existing
    
    // Check for file upload
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
            $error_msg = "Invalid file type.";
        }
    }
    // Check for URL (only if no file uploaded)
    elseif (!empty($_POST['logo_url'])) {
        $class_logo = $_POST['logo_url'];
    }

    if (empty($error_msg)) {
        // Keep logo_emoji as separate field if needed, or just keep old value if we removed it from form
        // Since we removed it from form, let's just keep the old one or set a default.
        // Actually, let's just use what's in DB or default. 
        // But the form doesn't send logo_emoji anymore.
        // So we should fetch the old one or just ignore it.
        // Let's assume we don't care about updating logo_emoji anymore.
        // But the SQL needs 10 params.
        
        // We'll just set it to '🏫' or keep it if we fetched it? 
        // We haven't fetched it yet in this block. 
        // Let's just set it to '🏫' to satisfy the query, or remove it from query.
        // Removing from query is cleaner.
        
        $stmt = $conn->prepare("UPDATE classes SET grade=?, institute_name=?, institute_address=?, institute_phone=?, class_day=?, start_time=?, end_time=?, description=?, class_logo=? WHERE id=?");
        $stmt->bind_param("issssssssi", $grade, $institute_name, $institute_address, $institute_phone, $class_day, $start_time, $end_time, $description, $class_logo, $class_id);

        if ($stmt->execute()) {
            $success_msg = "Class updated successfully!";
        } else {
            $error_msg = "Error updating class: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch class data
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();
$stmt->close();

if (!$class) {
    echo "Class not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
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
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 800px;
        }
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
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            margin-left: 1rem;
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
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1>Edit Class</h1>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="current_logo" value="<?php echo htmlspecialchars($class['class_logo']); ?>">
                <div style="display: flex; gap: 2rem;">
                    <div style="flex: 1;">
                        <div class="form-group">
                            <label class="form-label">Grade</label>
                            <select name="grade" class="form-control" required>
                                <option value="6" <?php if($class['grade'] == 6) echo 'selected'; ?>>Grade 6</option>
                                <option value="7" <?php if($class['grade'] == 7) echo 'selected'; ?>>Grade 7</option>
                                <option value="8" <?php if($class['grade'] == 8) echo 'selected'; ?>>Grade 8</option>
                                <option value="9" <?php if($class['grade'] == 9) echo 'selected'; ?>>Grade 9</option>
                                <option value="10" <?php if($class['grade'] == 10) echo 'selected'; ?>>Grade 10 (O/L)</option>
                                <option value="11" <?php if($class['grade'] == 11) echo 'selected'; ?>>Grade 11 (A/L)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Institute Name</label>
                            <input type="text" name="institute_name" class="form-control" value="<?php echo htmlspecialchars($class['institute_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Institute Address</label>
                            <input type="text" name="institute_address" class="form-control" value="<?php echo htmlspecialchars($class['institute_address']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="institute_phone" class="form-control" value="<?php echo htmlspecialchars($class['institute_phone']); ?>" required>
                        </div>
                    </div>
                    
                    <div style="flex: 1;">
                        <div class="form-group">
                            <label class="form-label">Class Day</label>
                            <select name="class_day" class="form-control" required>
                                <?php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($days as $day) {
                                    $selected = ($class['class_day'] == $day) ? 'selected' : '';
                                    echo "<option value='$day' $selected>$day</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">Start Time</label>
                                <input type="text" name="start_time" class="form-control" value="<?php echo htmlspecialchars($class['start_time']); ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">End Time</label>
                                <input type="text" name="end_time" class="form-control" value="<?php echo htmlspecialchars($class['end_time']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Institute Logo</label>
                            
                            <!-- Current Logo Preview -->
                            <div style="margin-bottom: 1rem; padding: 10px; border: 1px dashed #ccc; border-radius: 8px; text-align: center;">
                                <label style="display: block; font-size: 0.8rem; color: var(--gray); margin-bottom: 0.5rem;">Current Logo:</label>
                                <?php if (!empty($class['class_logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($class['class_logo']); ?>" alt="Current Logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 1px solid #eee;">
                                <?php else: ?>
                                    <div style="font-size: 2rem;"><?php echo !empty($class['logo_emoji']) ? htmlspecialchars($class['logo_emoji']) : '🏫'; ?></div>
                                    <span style="font-size: 0.8rem; color: var(--gray);">(No image set)</span>
                                <?php endif; ?>
                            </div>

                            <div style="margin-bottom: 0.5rem;">
                                <input type="text" name="logo_url" class="form-control" placeholder="New Image URL" value="<?php echo (filter_var($class['class_logo'], FILTER_VALIDATE_URL)) ? htmlspecialchars($class['class_logo']) : ''; ?>">
                            </div>
                            <div style="text-align: center; margin: 0.5rem 0; color: var(--gray); font-size: 0.9rem; font-weight: bold;">- OR -</div>
                            <div>
                                <input type="file" name="logo_upload" class="form-control" accept="image/*">
                            </div>
                            <small style="color: var(--gray); display: block; margin-top: 0.5rem;">Upload to replace. Leave both empty to keep current.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($class['description']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="update_class" class="btn">Update Class</button>
                <a href="admin_classes.php" class="btn btn-outline">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
