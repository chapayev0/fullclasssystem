<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid Security Token.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($current_password, $user['password'])) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_password_hash, $user_id);
                
                if ($update_stmt->execute()) {
                    $message = "Password updated successfully.";
                } else {
                    $error = "Error updating password. Please try again.";
                }
                $update_stmt->close();
            } else {
                $error = "Incorrect current password.";
            }
        }
        $stmt->close();
    }
}

$sql = "SELECT * FROM students WHERE user_id = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    // Should ideally not happen if data integrity is maintained
    $error = "Student profile not found. Please contact admin.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | ICT with Dilhara</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
                padding-top: 5rem;
            }
            .profile-grid {
                grid-template-columns: 1fr; /* Stack columns */
            }
            .section-header {
                grid-column: span 1; /* Match single column */
            }
        }
        .card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        h2 {
            margin-top: 0;
            color: var(--dark);
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .profile-item {
            margin-bottom: 1rem;
        }
        .label {
            display: block;
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        .value {
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
        }
        .full-width {
            grid-column: span 2;
        }
        .section-header {
            grid-column: span 2;
            color: var(--primary);
            font-weight: 700;
            margin-top: 1rem;
            font-size: 1.2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
    <script src="assest/js/password-toggle.js" defer></script>
</head>
<body>
    <?php include 'student_sidebar.php'; ?>
    <div class="main-content">
        <div class="card" style="margin-bottom: 2rem;">
            <?php if(isset($student)): ?>
                <h2>My Profile</h2>
                
                <div class="profile-grid">
                    <div class="section-header">Personal Information</div>
                    
                    <div class="profile-item">
                        <span class="label">First Name</span>
                        <div class="value"><?php echo htmlspecialchars($student['first_name']); ?></div>
                    </div>
                    <div class="profile-item">
                        <span class="label">Last Name</span>
                        <div class="value"><?php echo htmlspecialchars($student['last_name']); ?></div>
                    </div>
                    <div class="profile-item">
                        <span class="label">Grade</span>
                        <div class="value"><?php echo format_grade($student['grade']); ?></div>
                    </div>
                    <div class="profile-item">
                        <span class="label">Date of Birth</span>
                        <div class="value"><?php echo htmlspecialchars($student['dob']); ?></div>
                    </div>
                    <div class="profile-item">
                        <span class="label">Phone</span>
                        <div class="value"><?php echo htmlspecialchars($student['phone']); ?></div>
                    </div>
                    <div class="profile-item">
                        <span class="label">Gender</span>
                        <div class="value"><?php echo htmlspecialchars($student['gender']); ?></div>
                    </div>
                    <div class="profile-item full-width">
                        <span class="label">Address</span>
                        <div class="value"><?php echo htmlspecialchars($student['address']); ?></div>
                    </div>

                    <div class="section-header">Parent Information</div>
                    
                    <div class="profile-item">
                        <span class="label">Parent Name</span>
                        <div class="value"><?php echo htmlspecialchars($student['parent_name']); ?></div>
                    </div>
                    <div class="profile-item">
                        <span class="label">Relationship</span>
                        <div class="value"><?php echo htmlspecialchars($student['parent_relationship']); ?></div>
                    </div>
                    <div class="profile-item full-width">
                        <span class="label">Parent Contact</span>
                        <div class="value"><?php echo htmlspecialchars($student['parent_contact']); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p>Profile information unavailable.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Change Password</h2>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error && strpos($error, 'profile') === false): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php csrf_input(); ?>
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="update_password" class="btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>
