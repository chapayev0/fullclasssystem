<?php 
include 'db_connect.php'; 

// Validate and clean the grade input
$grade = isset($_GET['grade']) ? intval($_GET['grade']) : 0;
// Only allow grades 6-11
if ($grade < 6 || $grade > 11) {
    header("Location: index.php");
    exit();
}

$classes = [];
$stmt = $conn->prepare("SELECT * FROM classes WHERE grade = ?");
$stmt->bind_param("i", $grade);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade <?php echo $grade; ?> ICT Classes | ICT with Dilhara</title>
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
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            color: var(--dark);
            overflow-x: hidden;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navbar Styles (Copied from index.php) */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid var(--border);
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            font-family: 'Space Mono', monospace;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.7rem 1.8rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline {
            background: var(--light);
            border: 1px solid var(--border);
            color: var(--dark);
        }

        .btn-outline:hover {
            background: var(--secondary);
            border-color: var(--accent);
        }

        .btn-primary {
            background: var(--primary);
            color: var(--light);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        /* Page Content Styles */
        .page-header {
            margin-top: 80px;
            padding: 6rem 2rem 4rem;
            background: var(--secondary);
            text-align: center;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }

        .institutes-grid {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3rem;
            flex-grow: 1;
        }

        .institute-card {
            background: var(--light);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .institute-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: var(--secondary);
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .institute-logo-placeholder {
            width: 80px;
            height: 80px;
            background: var(--light);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden; /* Ensure image stays within circle */
        }

        .institute-logo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .institute-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        .card-body {
            padding: 2rem;
            flex-grow: 1;
        }

        .info-row {
            display: flex;
            margin-bottom: 1rem;
            align-items: flex-start;
        }

        .info-icon {
            width: 24px;
            margin-right: 1rem;
            text-align: center;
            font-size: 1.2rem;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .info-value {
            font-size: 1.1rem;
            color: var(--dark);
            font-weight: 500;
        }

        .description {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Dilhara Section Styles */
        .dilhara-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            padding: 60px 20px;
            background-color: var(--light);
            position: relative;
            overflow: hidden;
            border-top: 1px solid var(--border);
        }

        .dilhara-text {
            text-align: left;
            color: var(--dark);
            line-height: 1;
            position: relative;
            z-index: 2;
        }

        .dilhara-text .small-text {
            font-size: 12vw;
            font-weight: 500;
            letter-spacing: -0.02em;
            margin-bottom: -20px;
            color: var(--gray);
        }

        .dilhara-text .large-text {
            font-size: 28vw;
            font-weight: 600;
            letter-spacing: -0.03em;
            color: var(--primary);
        }

        
        /* Floating particles */
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: #60A5FA;
            border-radius: 50%;
            animation: float 3s ease-in-out infinite;
            opacity: 0.6;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-20px) translateX(10px); }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Page Header -->
    <header class="page-header">
        <h1 class="page-title">Grade <?php echo $grade; ?> ICT Classes</h1>
        <p class="page-subtitle">Start your journey into the digital world with our comprehensive Grade <?php echo $grade; ?> curriculum available at these leading institutes.</p>
    </header>

    <!-- Institutes Cards -->
    <section class="institutes-grid">
        <?php if (empty($classes)): ?>
            <div style="grid-column: span 2; text-align: center; color: var(--gray); font-size: 1.2rem; padding: 4rem;">
                <p>No classes scheduled for Grade <?php echo $grade; ?> at the moment. Please check back later.</p>
            </div>
        <?php else: ?>
            <?php foreach ($classes as $class): ?>
                <div class="institute-card">
                    <div class="card-header">
                        <div class="institute-logo-placeholder">
                            <?php 
                            $logoSrc = !empty($class['class_logo']) ? $class['class_logo'] : 'https://placehold.co/300x200?text=Class+Image'; 
                            ?>
                            <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Class Image">
                        </div>
                        <h3 class="institute-name">ICT Grade <?php echo htmlspecialchars($class['grade']); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-icon">📚</div>
                            <div class="info-content">
                                <div class="info-label">Subject</div>
                                <div class="info-value"><?php echo htmlspecialchars($class['subject']); ?></div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">📞</div>
                            <div class="info-content">
                                <div class="info-label">Contact</div>
                                <div class="info-value"><?php echo htmlspecialchars($class['institute_phone']); ?></div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">📅</div>
                            <div class="info-content">
                                <div class="info-label">Class Day</div>
                                <div class="info-value"><?php echo htmlspecialchars($class['class_day']); ?></div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">⏰</div>
                            <div class="info-content">
                                <div class="info-label">Time</div>
                                <div class="info-value"><?php echo htmlspecialchars($class['start_time']) . ' - ' . htmlspecialchars($class['end_time']); ?></div>
                            </div>
                        </div>
                        <?php if (!empty($class['description'])): ?>
                            <div class="description">
                                <?php echo htmlspecialchars($class['description']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Dilhara Section -->
    <?php include 'ict_section.php'; ?>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>
