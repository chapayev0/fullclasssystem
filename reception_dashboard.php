<?php
session_start();
include 'db_connect.php';

// Check if receptionist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reception') {
    header("Location: login.php");
    exit();
}

// Fetch some stats for receptionist
$stats = [
    'students' => $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0],
    'classes' => $conn->query("SELECT COUNT(*) FROM classes")->fetch_row()[0],
    'attendance_today' => $conn->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE()")->fetch_row()[0]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception Dashboard | ICT with Dilhara</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #10B981;
            --secondary: #3B82F6;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .header { margin-bottom: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .stat-value { font-size: 2rem; font-weight: 800; color: var(--dark); display: block; margin-bottom: 0.5rem; }
        .stat-label { color: var(--gray); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
        
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .action-card { background: white; padding: 1.5rem; border-radius: 12px; text-decoration: none; color: var(--dark); border: 2px solid transparent; transition: all 0.3s; display: flex; align-items: center; gap: 1rem; }
        .action-card:hover { border-color: var(--primary); transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .action-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } }
    </style>
</head>
<body>
    <?php include 'reception_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Reception Dashboard</h1>
            <p style="color: var(--gray);">Welcome back. Here's what's happening today.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?php echo $stats['students']; ?></span>
                <span class="stat-label">Total Students</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $stats['classes']; ?></span>
                <span class="stat-label">Active Classes</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $stats['attendance_today']; ?></span>
                <span class="stat-label">Attendance Today</span>
            </div>
        </div>

        <h2 style="margin-bottom: 1.5rem;">Quick Actions</h2>
        <div class="quick-actions">
            <a href="admin_attendance.php" class="action-card">
                <div class="action-icon" style="background: #ECFDF5; color: #059669;"><i class="fas fa-calendar-check"></i></div>
                <div><strong>Mark Attendance</strong><div style="font-size: 0.8rem; color: var(--gray);">Track daily presence</div></div>
            </a>
            <a href="add_student.php" class="action-card">
                <div class="action-icon" style="background: #EFF6FF; color: #2563EB;"><i class="fas fa-user-plus"></i></div>
                <div><strong>Add Student</strong><div style="font-size: 0.8rem; color: var(--gray);">Register new pupil</div></div>
            </a>
            <a href="admin_students.php" class="action-card">
                <div class="action-icon" style="background: #F5F3FF; color: #7C3AED;"><i class="fas fa-users"></i></div>
                <div><strong>View Students</strong><div style="font-size: 0.8rem; color: var(--gray);">Manage details</div></div>
            </a>
            <a href="admin_messages.php" class="action-card">
                <div class="action-icon" style="background: #FFFBEB; color: #D97706;"><i class="fas fa-comments"></i></div>
                <div><strong>Messages</strong><div style="font-size: 0.8rem; color: var(--gray);">Send notifications</div></div>
            </a>
        </div>
    </div>
</body>
</html>
