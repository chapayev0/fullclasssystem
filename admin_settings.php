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
    $teacher_share = intval($_POST['teacher_share_percentage']);
    
    if ($teacher_share < 0 || $teacher_share > 100) {
        $error_msg = "Percentage must be between 0 and 100.";
    } else {
        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'teacher_share_percentage'");
        $stmt->bind_param("s", $teacher_share);
        if ($stmt->execute()) {
            $success_msg = "Settings updated successfully!";
        } else {
            $error_msg = "Error updating settings: " . $conn->error;
        }
    }
}

// Fetch Current Settings
$settings = [];
$res = $conn->query("SELECT * FROM site_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$teacher_share = isset($settings['teacher_share_percentage']) ? intval($settings['teacher_share_percentage']) : 80;
$institute_share = 100 - $teacher_share;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Settings | Admin</title>
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
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 600px; }
        .header { margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark); }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .share-preview { 
            display: flex; 
            gap: 1rem; 
            margin-top: 1rem; 
            padding: 1rem; 
            background: #f1f5f9; 
            border-radius: 8px; 
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Finance Settings</h1>
            <p style="color: var(--gray);">Configure profit share percentages for the institute and teachers.</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="card">
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

                <div class="share-preview">
                    <div style="color: var(--primary);">Teacher: <span id="teacherSpan"><?php echo $teacher_share; ?></span>%</div>
                    <div style="color: var(--dark);">Institute: <span id="instituteSpan"><?php echo $institute_share; ?></span>%</div>
                </div>

                <button type="submit" name="update_settings" class="btn" style="margin-top: 2rem; width: 100%;">Save Changes</button>
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
