<?php
include 'db_connect.php';

$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($teacher_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch teacher details
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    header("Location: index.php");
    exit();
}

// Fetch subjects taught by this teacher
$subjects_stmt = $conn->prepare("SELECT * FROM subjects WHERE teacher_id = ?");
$subjects_stmt->bind_param("i", $teacher_id);
$subjects_stmt->execute();
$subjects_result = $subjects_stmt->get_result();
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}
$subjects_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($teacher['name']); ?> | Teacher Profile</title>
    <link rel="icon" type="image/png" href="assest/logo/logo1.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
            --primary-hover: #333333;
            --secondary: #F8F8FB;
            --accent: #E5E5E8;
            --dark: #1A1A1A;
            --light: #FFFFFF;
            --gray: #6B7280;
            --gray-light: #F3F4F6;
            --border: #E5E7EB;
            --shadow: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--secondary); color: var(--dark); line-height: 1.6; }

        .profile-container { max-width: 1000px; margin: 0 auto; padding: 120px 2rem 5rem; }

        .profile-card {
            background: var(--light);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
        }

        .profile-header {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            padding: 4rem 2rem;
            text-align: center;
            color: white;
            position: relative;
        }

        .profile-image-wrapper {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 6px solid var(--light);
            margin: 0 auto -90px;
            overflow: hidden;
            position: relative;
            z-index: 2;
            background: white;
            box-shadow: var(--shadow-md);
        }

        .profile-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-body {
            padding: 110px 3rem 4rem;
            text-align: center;
        }

        .teacher-name {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .teacher-qual {
            font-size: 1.1rem;
            color: var(--gray);
            font-style: italic;
            margin-bottom: 2rem;
            font-family: 'Space Mono', monospace;
        }

        .teacher-bio {
            font-size: 1.15rem;
            color: #444;
            max-width: 800px;
            margin: 0 auto 3rem;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .subjects-section {
            margin-top: 3rem;
            text-align: left;
            border-top: 1px solid var(--border);
            padding-top: 3rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .subject-tag {
            background: var(--secondary);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .subject-tag:hover {
            transform: translateY(-3px);
            background: white;
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .subject-icon {
            font-size: 1.8rem;
            min-width: 50px;
            height: 50px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
        }

        .subject-info h4 { font-weight: 700; font-size: 1.1rem; }
        .subject-info p { font-size: 0.85rem; color: var(--gray); margin-top: 2px; }

        .contact-bar {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .contact-pill {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1.5rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 50px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .contact-pill:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .profile-body { padding: 100px 1.5rem 3rem; }
            .teacher-name { font-size: 2rem; }
            .contact-bar { flex-wrap: wrap; }
            .profile-header { padding: 3rem 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-image-wrapper">
                    <img src="<?php echo htmlspecialchars($teacher['image']); ?>" alt="<?php echo htmlspecialchars($teacher['name']); ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($teacher['name']); ?>&size=200&background=random'">
                </div>
            </div>
            
            <div class="profile-body">
                <h1 class="teacher-name"><?php echo htmlspecialchars($teacher['name']); ?></h1>
                <p class="teacher-qual"><?php echo htmlspecialchars($teacher['qualifications']); ?></p>

                <div class="contact-bar">
                    <?php if(!empty($teacher['phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($teacher['phone']); ?>" class="contact-pill"><span>📞</span> Call</a>
                    <?php endif; ?>
                    
                    <?php if(!empty($teacher['whatsapp'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $teacher['whatsapp']); ?>" target="_blank" class="contact-pill"><span>💬</span> WhatsApp</a>
                    <?php endif; ?>
                    
                    <?php if(!empty($teacher['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($teacher['email']); ?>" class="contact-pill"><span>📧</span> Email</a>
                    <?php endif; ?>
                </div>

                <div class="teacher-bio">
                    <?php echo nl2br(htmlspecialchars($teacher['bio'])); ?>
                </div>

                <?php if(!empty($subjects)): ?>
                <div class="subjects-section">
                    <h3 class="section-title"><span>📚</span> Subjects Taught</h3>
                    <div class="subjects-grid">
                        <?php foreach($subjects as $s): ?>
                            <a href="timetable.php?subject=<?php echo urlencode($s['name']); ?>" class="subject-tag">
                                <div class="subject-icon"><?php echo $s['logo_emoji'] ? $s['logo_emoji'] : '📖'; ?></div>
                                <div class="subject-info">
                                    <h4><?php echo htmlspecialchars($s['name']); ?></h4>
                                    <p>View Timetable</p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
