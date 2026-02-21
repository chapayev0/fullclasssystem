<?php
include 'db_connect.php';

$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';

$sql = "SELECT c.*, s.description as subject_desc, s.subject_logo, s.logo_emoji as subject_emoji, 
               t.name as teacher_name, t.image as teacher_image, t.qualifications as teacher_qual, 
               t.phone as teacher_phone, t.whatsapp as teacher_whatsapp, t.email as teacher_email, 
               t.website as teacher_website, t.bio as teacher_bio
        FROM classes c
        LEFT JOIN subjects s ON c.subject = s.name
        LEFT JOIN teachers t ON s.teacher_id = t.id";

if ($subject_filter) {
    $sql .= " WHERE c.subject = '" . $conn->real_escape_string($subject_filter) . "'";
}

$sql .= " ORDER BY c.grade ASC, FIELD(c.class_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), c.start_time ASC";

$result = $conn->query($sql);
$classes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Timetable | ICT with Dilhara</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
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

        .container { max-width: 1200px; margin: 0 auto; padding: 120px 2rem 5rem; }
        
        .page-header { text-align: center; margin-bottom: 4rem; }
        .page-title { font-size: 3rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary); }
        .page-subtitle { font-size: 1.2rem; color: var(--gray); }

        .timetable-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
            gap: 2rem;
        }

        .timetable-card {
            background: var(--light);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            display: flex;
            border: 1px solid var(--border);
            transition: transform 0.3s ease;
        }

        .timetable-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }

        .class-info {
            flex: 1.5;
            padding: 2.5rem;
            border-right: 1px solid var(--border);
        }

        .teacher-info {
            flex: 1;
            padding: 2.5rem;
            background: #fafafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .grade-badge {
            background: var(--primary);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .subject-name { font-size: 2rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary); }
        
        .schedule-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .schedule-icon { font-size: 1.5rem; }

        .class-desc { color: var(--gray); font-size: 1rem; margin-top: 1.5rem; }

        .teacher-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
            margin-bottom: 1rem;
        }

        .teacher-name { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.3rem; }
        .teacher-qual { font-size: 0.9rem; color: var(--gray); font-style: italic; margin-bottom: 1rem; }

        .contact-links { display: flex; gap: 0.8rem; margin-top: auto; }
        .contact-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .contact-btn:hover { background: var(--primary); color: white; border-color: var(--primary); transform: scale(1.1); }

        @media (max-width: 968px) {
            .timetable-card { flex-direction: column; }
            .class-info { border-right: none; border-bottom: 1px solid var(--border); }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Class Timetable</h1>
            <p class="page-subtitle">
                <?php if ($subject_filter): ?>
                    Showing schedules for <strong><?php echo htmlspecialchars($subject_filter); ?></strong>
                <?php else: ?>
                    Explore all our class schedules and meet your instructors.
                <?php endif; ?>
            </p>
        </div>

        <div class="timetable-grid">
            <?php if (empty($classes)): ?>
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 20px; box-shadow: var(--shadow);">
                    <p style="font-size: 1.2rem; color: var(--gray);">No classes found for this subject.</p>
                    <a href="timetable.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">View All Classes</a>
                </div>
            <?php else: ?>
                <?php foreach ($classes as $c): ?>
                    <div class="timetable-card">
                        <div class="class-info">
                            <span class="grade-badge">Grade <?php echo $c['grade']; ?></span>
                            <h2 class="subject-name"><?php echo htmlspecialchars($c['subject']); ?></h2>
                            
                            <div class="schedule-item">
                                <span class="schedule-icon">📅</span>
                                <strong><?php echo htmlspecialchars($c['class_day']); ?></strong>
                            </div>
                            <div class="schedule-item">
                                <span class="schedule-icon">⏰</span>
                                <?php echo htmlspecialchars($c['start_time']); ?> - <?php echo htmlspecialchars($c['end_time']); ?>
                            </div>
                            <div class="schedule-item">
                                <span class="schedule-icon">📞</span>
                                <?php echo htmlspecialchars($c['institute_phone']); ?>
                            </div>

                            <p class="class-desc"><?php echo htmlspecialchars($c['description']); ?></p>
                        </div>

                        <div class="teacher-info">
                            <?php if ($c['teacher_name']): ?>
                                <img src="<?php echo htmlspecialchars($c['teacher_image']); ?>" alt="<?php echo htmlspecialchars($c['teacher_name']); ?>" class="teacher-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($c['teacher_name']); ?>&background=random'">
                                <h3 class="teacher-name"><?php echo htmlspecialchars($c['teacher_name']); ?></h3>
                                <p class="teacher-qual"><?php echo htmlspecialchars($c['teacher_qual']); ?></p>
                                
                                <div class="contact-links">
                                    <?php if($c['teacher_phone']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($c['teacher_phone']); ?>" class="contact-btn" title="Call">📞</a>
                                    <?php endif; ?>
                                    <?php if($c['teacher_whatsapp']): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $c['teacher_whatsapp']); ?>" target="_blank" class="contact-btn" title="WhatsApp">💬</a>
                                    <?php endif; ?>
                                    <?php if($c['teacher_email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($c['teacher_email']); ?>" class="contact-btn" title="Email">📧</a>
                                    <?php endif; ?>
                                    <?php if($c['teacher_website']): ?>
                                        <a href="<?php echo htmlspecialchars($c['teacher_website']); ?>" target="_blank" class="contact-btn" title="Website">🌐</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div style="margin: auto;">
                                    <p style="color: var(--gray);">No teacher assigned yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
