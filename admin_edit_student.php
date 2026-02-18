<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$student_id) {
    header("Location: admin_students.php");
    exit();
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $dob = $_POST['dob'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $parent_name = mysqli_real_escape_string($conn, $_POST['parent_name']);
    $parent_contact = mysqli_real_escape_string($conn, $_POST['parent_contact']);
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];

    $conn->begin_transaction();
    try {
        // Update Student Profile
        $sql = "UPDATE students SET first_name='$first_name', last_name='$last_name', dob='$dob', phone='$phone', grade='$grade', address='$address', parent_name='$parent_name', parent_contact='$parent_contact' WHERE id=$student_id";
        $conn->query($sql);

        // Update Password if provided
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hashed' WHERE id=$user_id");
        }
        
        $conn->commit();
        $success_msg = "Student details updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Error updating student: " . $e->getMessage();
    }
}

// Fetch Data
$sql = "SELECT s.*, u.username, u.id as u_id FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = $student_id";
$result = $conn->query($sql);
$student = $result->fetch_assoc();

if (!$student) {
    echo "Student not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .sidebar { width: 250px; background: var(--dark); color: white; min-height: 100vh; padding: 2rem; position: fixed; left: 0; top: 0; }
        .logo { font-size: 1.5rem; font-weight: 800; margin-bottom: 3rem; color: var(--primary); }
        .nav-link { display: block; color: rgba(255,255,255,0.7); text-decoration: none; padding: 1rem 0; transition: color 0.3s; }
        .nav-link:hover, .nav-link.active { color: white; font-weight: 600; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 800px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .full-width { grid-column: span 2; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); margin-left: 1rem; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        
        .info-pill { background: #e0f2fe; padding: 0.5rem 1rem; border-radius: 20px; color: #0284c7; font-weight: 600; font-size: 0.9rem; display: inline-block; margin-bottom: 1rem;}
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1>Edit Student</h1>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="info-pill">
                Username: <?php echo htmlspecialchars($student['username']); ?>
            </div>
            
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $student['u_id']; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($student['dob']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grade</label>
                        <select name="grade" class="form-control" required>
                            <?php for($i=6; $i<=11; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if($student['grade'] == $i) echo 'selected'; ?>>Grade <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Parent Name</label>
                        <input type="text" name="parent_name" class="form-control" value="<?php echo htmlspecialchars($student['parent_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Parent Contact</label>
                        <input type="tel" name="parent_contact" class="form-control" value="<?php echo htmlspecialchars($student['parent_contact']); ?>" required>
                    </div>
                    
                    <div class="form-group full-width" style="border-top: 1px solid #e2e8f0; padding-top: 1.5rem; margin-top: 1rem;">
                        <label class="form-label">Reset Password (Optional)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Leave empty to keep current password">
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" name="update_student" class="btn">Update Details</button>
                    <a href="admin_students.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
