<?php
session_start();
include 'db_connect.php';

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
    <title>Teacher Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --dark: #0F172A;
            --light: #F8FAFC;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card { background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 90%; }
        h1 { color: var(--dark); margin-bottom: 1rem; }
        p { color: #64748B; margin-bottom: 2rem; }
        .btn-logout { background: #EF4444; color: white; padding: 0.8rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome, <?php echo htmlspecialchars($teacher['name']); ?>!</h1>
        <p>This is your teacher dashboard. Your account has been successfully created and linked.</p>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</body>
</html>
