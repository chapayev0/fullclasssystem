<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Analytics Queries
// 1. Total Counts
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_classes = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];

$analytics = [];
for ($i = 6; $i <= 11; $i++) {
    $analytics[$i] = [
        'student_count' => 0
    ];
}

// Fetch Student Counts per Grade
$stu_res = $conn->query("SELECT grade, COUNT(*) as count FROM students GROUP BY grade");
while ($row = $stu_res->fetch_assoc()) {
    $g = intval($row['grade']);
    if (isset($analytics[$g])) {
        $analytics[$g]['student_count'] = $row['count'];
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ICT with Dilhara</title>
    <link rel="icon" type="image/png" href="assest/logo/logo1.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --success: #10B981;
            --warning: #F59E0B;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .sidebar { width: 250px; background: var(--dark); color: white; min-height: 100vh; padding: 2rem; position: fixed; left: 0; top: 0; }
        .logo { font-size: 1.5rem; font-weight: 800; margin-bottom: 3rem; color: var(--primary); }
        .nav-link { display: block; color: rgba(255,255,255,0.7); text-decoration: none; padding: 1rem 0; transition: color 0.3s; }
        .nav-link:hover, .nav-link.active { color: white; font-weight: 600; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem; }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .icon-blue { background: #E0F2FE; color: var(--primary); }
        .icon-purple { background: #F3E8FF; color: var(--secondary); }
        .icon-green { background: #D1FAE5; color: var(--success); }
        .icon-orange { background: #FEF3C7; color: var(--warning); }
        
        .stat-info h3 { margin: 0; font-size: 2rem; color: var(--dark); font-weight: 700; }
        .stat-info p { margin: 0; color: var(--gray); font-size: 0.9rem; font-weight: 600; }
        
        /* Grade Analytics */
        .section-title { font-size: 1.2rem; font-weight: 700; color: var(--dark); margin-bottom: 1.5rem; }
        
        .grades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .grade-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #E2E8F0;
        }
        .grade-header {
            background: #F8FAFC;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .grade-title { font-weight: 700; color: var(--dark); font-size: 1.1rem; }
        .student-badge { background: var(--primary); color: white; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Dashboard</h1>
                <span style="color: var(--gray);">Welcome back, Admin</span>
            </div>
            <a href="admin_students.php" style="background: var(--dark); color: white; padding: 0.8rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600;">Manage Users</a>
        </div>
        
        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">👥</div>
                <div class="stat-info">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green">🏫</div>
                <div class="stat-info">
                    <h3><?php echo $total_classes; ?></h3>
                    <p>Active Classes</p>
                </div>
            </div>
        </div>
        
        <!-- Grade Analytics -->
        <div class="section-title">Academic Overview (Grade 6-11)</div>
        <div class="grades-grid">
            <?php foreach ($analytics as $grade => $data): ?>
                <div class="grade-card">
                    <div class="grade-header">
                        <div class="grade-title">Grade <?php echo $grade; ?></div>
                        <div class="student-badge"><?php echo $data['student_count']; ?> Students</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    </div>
</body>
</html>
