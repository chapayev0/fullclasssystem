<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Teacher Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0066FF; --dark: #0F172A; --light: #F8FAFC; --gray: #64748B; }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        .card { background: white; padding: 2.5rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 800px; }
        .profile-item { margin-bottom: 1.5rem; }
        .label { color: var(--gray); font-size: 0.9rem; margin-bottom: 0.3rem; display: block; }
        .value { color: var(--dark); font-weight: 600; font-size: 1.1rem; }
         @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } }
    </style>
</head>
<body>
    <?php include 'teacher_sidebar.php'; ?>
    <div class="main-content">
        <h1>My Profile</h1>
        <div class="card">
            <div class="profile-item">
                <span class="label">Full Name</span>
                <div class="value"><?php echo htmlspecialchars($teacher['name']); ?></div>
            </div>
            <div class="profile-item">
                <span class="label">Email Address</span>
                <div class="value"><?php echo htmlspecialchars($teacher['email']); ?></div>
            </div>
            <div class="profile-item">
                <span class="label">Qualifications</span>
                <div class="value"><?php echo htmlspecialchars($teacher['qualifications']); ?></div>
            </div>
            <div class="profile-item">
                <span class="label">Phone</span>
                <div class="value"><?php echo htmlspecialchars($teacher['phone']); ?></div>
            </div>
        </div>
    </div>
</body>
</html>
