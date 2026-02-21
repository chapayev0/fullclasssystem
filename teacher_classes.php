<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, name FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

$teacher_id = $teacher['id'];
$classes = [];
$sql_classes = "SELECT c.* FROM classes c 
                JOIN subjects s ON c.subject = s.name 
                WHERE s.teacher_id = ?";
$stmt_cls = $conn->prepare($sql_classes);
$stmt_cls->bind_param("i", $teacher_id);
$stmt_cls->execute();
$res_cls = $stmt_cls->get_result();
while ($row = $res_cls->fetch_assoc()) {
    $classes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes | Teacher Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0066FF; --dark: #0F172A; --light: #F8FAFC; --gray: #64748B; }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .card { background: white; padding: 1.5rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h1 { margin-bottom: 2rem; }
         @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } }
    </style>
</head>
<body>
    <?php include 'teacher_sidebar.php'; ?>
    <div class="main-content">
        <h1>My Classes</h1>
        <div class="class-grid">
            <?php foreach ($classes as $class): ?>
                <div class="card">
                    <span style="background:var(--primary); color:white; padding:2px 8px; border-radius:10px; font-size:0.8rem;"><?php echo format_grade($class['grade']); ?></span>
                    <h3><?php echo htmlspecialchars($class['subject']); ?></h3>
                    <p style="color:var(--gray);"><?php echo htmlspecialchars($class['class_day']); ?> | <?php echo htmlspecialchars($class['start_time']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
