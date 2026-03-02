<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    if (isset($_POST['teacher_share_percentage'])) {
        $teacher_share = intval($_POST['teacher_share_percentage']);
        if ($teacher_share < 0 || $teacher_share > 100) {
            $error_msg = "Percentage must be between 0 and 100.";
        } else {
            $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'teacher_share_percentage'");
            $stmt->bind_param("s", $teacher_share);
            $stmt->execute();
            $success_msg = "Settings updated successfully!";
        }
    }
}

// Handle General Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_general_settings'])) {
    $inst_name = $_POST['institute_name'];
    $site_phone = $_POST['site_phone'];
    $site_email = $_POST['site_email'];
    $site_address = $_POST['site_address'];

    // Update text settings
    $text_settings = [
        'institute_name' => $inst_name,
        'site_phone' => $site_phone,
        'site_email' => $site_email,
        'site_address' => $site_address
    ];

    foreach ($text_settings as $key => $val) {
        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $val, $key);
        $stmt->execute();
    }

    // Handle Logo Upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/site/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $file_name = 'logo_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target_file)) {
            $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'site_logo'");
            $stmt->bind_param("s", $target_file);
            $stmt->execute();
        }
    }

    $success_msg = "General settings updated successfully!";
}

// Fetch Current Settings
$settings = [];
$res = $conn->query("SELECT * FROM site_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$teacher_share = isset($settings['teacher_share_percentage']) ? intval($settings['teacher_share_percentage']) : 80;
$institute_share = 100 - $teacher_share;
$inst_name = $settings['institute_name'] ?? '';
$site_logo = $settings['site_logo'] ?? '';
$site_phone = $settings['site_phone'] ?? '';
$site_email = $settings['site_email'] ?? '';
$site_address = $settings['site_address'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --success: #10B981;
            --danger: #EF4444;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 800px; margin-bottom: 2rem; }
        .header { margin-bottom: 2rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark); }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .section-title { margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #f1f5f9; color: var(--dark); }
        .logo-preview { width: 150px; height: auto; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Site Settings</h1>
            <p style="color: var(--gray);">Manage your institute details, logo, and finance configurations.</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 class="section-title">General Settings</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Institute Name</label>
                    <input type="text" name="institute_name" class="form-control" value="<?php echo htmlspecialchars($inst_name); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Site Logo</label>
                    <?php if ($site_logo): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="Current Logo" class="logo-preview">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="site_logo" class="form-control" accept="image/*">
                    <small style="color: var(--gray);">Recommended: 300x100px PNG with transparency.</small>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($site_phone); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($site_email); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Physical Address</label>
                    <textarea name="site_address" class="form-control" rows="3" required><?php echo htmlspecialchars($site_address); ?></textarea>
                </div>

                <button type="submit" name="update_general_settings" class="btn">Save General Settings</button>
            </form>
        </div>

        <div class="card">
            <h2 class="section-title">Finance Settings</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Teacher Share Percentage (%)</label>
                    <input type="number" name="teacher_share_percentage" id="teacherShareInput" class="form-control" value="<?php echo $teacher_share; ?>" min="0" max="100" required oninput="updatePreview()">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Institute Share Percentage (%)</label>
                    <input type="number" id="instituteShareInput" class="form-control" value="<?php echo $institute_share; ?>" disabled>
                    <small style="color: var(--gray);">Automatically calculated as 100% - Teacher Share.</small>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; padding: 1rem; background: #f1f5f9; border-radius: 8px; font-weight: 600;">
                    <div style="color: var(--primary);">Teacher: <span id="teacherSpan"><?php echo $teacher_share; ?></span>%</div>
                    <div style="color: var(--dark);">Institute: <span id="instituteSpan"><?php echo $institute_share; ?></span>%</div>
                </div>

                <button type="submit" name="update_settings" class="btn">Save Finance Settings</button>
            </form>
        </div>
    </div>

    <script>
        function updatePreview() {
            const teacherShare = parseInt(document.getElementById('teacherShareInput').value) || 0;
            const instShare = Math.max(0, 100 - teacherShare);
            
            document.getElementById('instituteShareInput').value = instShare;
            document.getElementById('teacherSpan').innerText = teacherShare;
            document.getElementById('instituteSpan').innerText = instShare;
        }
    </script>
</body>
</html>
