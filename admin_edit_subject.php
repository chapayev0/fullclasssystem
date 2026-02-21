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
$subject = null;

// Get Subject Details
if (isset($_GET['id'])) {
    $subject_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $subject = $result->fetch_assoc();
    } else {
        $error_msg = "Subject not found.";
    }
    $stmt->close();
} else {
    header("Location: admin_subjects.php");
    exit();
}

// Fetch all active teachers for the dropdown
$teachers_list = [];
$t_result = $conn->query("SELECT id, name FROM teachers WHERE status = 'active' ORDER BY name ASC");
if ($t_result) {
    while ($t_row = $t_result->fetch_assoc()) {
        $teachers_list[] = $t_row;
    }
}

// Handle Update Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subject'])) {
    $name = $_POST['name'];
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    $description = $_POST['description'];
    $subject_logo = $subject['subject_logo'];
    
    // Handle Logo Upload
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assest/images/subject_logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            // Delete old file if it exists and is local
            if (!empty($subject_logo) && strpos($subject_logo, 'assest/') === 0 && file_exists($subject_logo)) {
                unlink($subject_logo);
            }
            
            $new_filename = uniqid('subject_logo_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $upload_path)) {
                $subject_logo = $upload_path;
            } else {
                $error_msg = "Failed to upload image.";
            }
        } else {
            $error_msg = "Invalid file type.";
        }
    } 
    // Handle Logo URL
    elseif (!empty($_POST['logo_url'])) {
        // Delete old file if it was a local upload
        if (!empty($subject_logo) && strpos($subject_logo, 'assest/') === 0 && file_exists($subject_logo)) {
            unlink($subject_logo);
        }
        $subject_logo = $_POST['logo_url'];
    }

    if (empty($error_msg)) {
        $stmt = $conn->prepare("UPDATE subjects SET name = ?, teacher_id = ?, description = ?, subject_logo = ? WHERE id = ?");
        $stmt->bind_param("sissi", $name, $teacher_id, $description, $subject_logo, $subject_id);

        if ($stmt->execute()) {
            $success_msg = "Subject updated successfully!";
            // Redirect to refresh data
            header("Location: admin_subjects.php?success=" . urlencode($success_msg));
            exit();
        } else {
            $error_msg = "Error updating subject: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --danger: #EF4444;
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
            margin-left: 250px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            padding: 2.5rem;
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
            box-sizing: border-box;
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
        .btn-gray {
            background: #E2E8F0;
            color: #475569;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
        }
        .current-logo {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-top: 10px;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
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
            <div>
                <h1>Edit Subject</h1>
                <a href="admin_subjects.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">← Back to Subjects</a>
            </div>
        </div>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php if ($subject): ?>
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($subject['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assign Teacher</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">-- No Teacher --</option>
                            <?php foreach ($teachers_list as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($subject['teacher_id'] == $t['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--gray);">Change the assigned teacher for this subject.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($subject['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Update Subject Image</label>
                        <div style="margin-bottom: 0.5rem;">
                            <input type="text" name="logo_url" class="form-control" placeholder="Image URL" value="<?php echo (!strpos($subject['subject_logo'], 'assest/') === 0) ? htmlspecialchars($subject['subject_logo']) : ''; ?>">
                        </div>
                        <div style="text-align: center; margin: 0.5rem 0; color: var(--gray); font-size: 0.9rem; font-weight: bold;">- OR -</div>
                        <div>
                            <input type="file" name="logo_upload" class="form-control" accept="image/*">
                        </div>
                        
                        <div style="margin-top: 1.5rem;">
                            <label class="form-label">Current Image</label>
                            <div class="current-logo">
                                <?php if (!empty($subject['subject_logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($subject['subject_logo']); ?>" alt="Subject Logo" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <span style="font-size: 2.5rem;"><?php echo htmlspecialchars($subject['logo_emoji']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="submit" name="update_subject" class="btn">Update Subject</button>
                        <a href="admin_subjects.php" class="btn btn-gray">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
