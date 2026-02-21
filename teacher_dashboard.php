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

if (!$teacher) {
    die("Teacher profile not found. Please contact admin.");
}

// Fetch classes taught by this teacher
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

// Fetch unique student count for this teacher
$sql_count = "SELECT COUNT(DISTINCT se.student_id) as student_count 
              FROM student_enrollments se
              JOIN classes c ON se.class_id = c.id
              JOIN subjects s ON c.subject = s.name
              WHERE s.teacher_id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $teacher_id);
$stmt_count->execute();
$count_res = $stmt_count->get_result()->fetch_assoc();
$student_count = $count_res['student_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | ICT with Dilhara</title>
    <link rel="icon" type="image/png" href="assest/logo/logo1.png">
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

        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
                padding-top: 5rem;
            }
        }
        
        .welcome-section {
            margin-bottom: 3rem;
        }

        .welcome-section h1 {
            color: var(--dark);
            margin: 0;
            font-size: 2.5rem;
            font-weight: 800;
        }

        .welcome-section p {
            color: var(--gray);
            margin: 0.5rem 0 0;
            font-size: 1.1rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .class-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .class-card {
            background: #F1F5F9;
            padding: 1.5rem;
            border-radius: 15px;
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .class-card:hover {
            transform: translateY(-5px);
            background: #E2E8F0;
        }

        .class-grade {
            background: var(--primary);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 0.8rem;
        }

        .class-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .class-time {
            color: var(--gray);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-summary {
            text-align: center;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 4px solid var(--primary);
        }

        .profile-name {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--dark);
            margin: 0;
        }

        .profile-qual {
            color: var(--primary);
            font-weight: 600;
            margin: 0.3rem 0;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            background: #F8FAFC;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <?php include 'teacher_sidebar.php'; ?>

    <div class="main-content">
        <div class="welcome-section">
            <h1>Hello, <?php echo htmlspecialchars($teacher['name']); ?></h1>
            <p>Welcome to your teaching hub. Manage your classes and resources effortlesly.</p>
        </div>

        <div class="dashboard-grid">
            <div class="main-stats">
                <div class="card">
                    <div class="section-title">
                        Your Assigned Classes
                        <span style="font-size: 0.9rem; color: var(--gray); font-weight: 400;"><?php echo count($classes); ?> classes active</span>
                    </div>
                    
                    <?php if (empty($classes)): ?>
                        <p style="color: var(--gray);">No classes assigned to you yet.</p>
                    <?php else: ?>
                        <div class="class-list">
                            <?php foreach ($classes as $class): ?>
                                <a href="class_details.php?id=<?php echo $class['id']; ?>" class="class-card">
                                    <span class="class-grade"><?php echo format_grade($class['grade']); ?></span>
                                    <div class="class-name"><?php echo htmlspecialchars($class['subject']); ?></div>
                                    <div class="class-time">
                                        📅 <?php echo htmlspecialchars($class['class_day']); ?> | ⏰ <?php echo htmlspecialchars($class['start_time']); ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="section-title">Quick Tasks</div>
                    <div style="display: flex; gap: 1rem;">
                        <a href="teacher_profile.php" style="flex: 1; padding: 1rem; background: #e0f2fe; color: #0369a1; border-radius: 12px; text-decoration: none; font-weight: 700; text-align: center;">Update Profile</a>
                        <a href="teacher_messages.php" style="flex: 1; padding: 1rem; background: #fef3c7; color: #92400e; border-radius: 12px; text-decoration: none; font-weight: 700; text-align: center;">View Messages</a>
                    </div>
                </div>
            </div>

            <div class="side-panel">
                <div class="card profile-summary">
                    <img src="<?php echo htmlspecialchars($teacher['image']); ?>" class="profile-pic" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($teacher['name']); ?>&background=random'">
                    <h2 class="profile-name"><?php echo htmlspecialchars($teacher['name']); ?></h2>
                    <p class="profile-qual"><?php echo htmlspecialchars($teacher['qualifications']); ?></p>
                    
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo count($classes); ?></div>
                            <div class="stat-label">Classes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $student_count; ?></div>
                            <div class="stat-label">Students</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-title" style="font-size: 1.2rem;">Contact Info</div>
                    <div style="font-size: 0.9rem; color: var(--gray);">
                        <div style="margin-bottom: 0.5rem;">📧 <?php echo htmlspecialchars($teacher['email']); ?></div>
                        <div style="margin-bottom: 0.5rem;">📞 <?php echo htmlspecialchars($teacher['phone']); ?></div>
                        <div>💬 <?php echo htmlspecialchars($teacher['whatsapp']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
