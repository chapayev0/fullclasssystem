<?php
session_start();
include 'db_connect.php';

// Get selected category
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : NULL;
$selected_grade = isset($_GET['grade']) ? trim($_GET['grade']) : NULL;

// Get all categories
$categories_query = "SELECT * FROM playground_categories WHERE status = 'active' ORDER BY display_order ASC";
$categories_result = $conn->query($categories_query);

// Build games query based on filters
$games_query = "SELECT DISTINCT g.* FROM playground_games g 
                LEFT JOIN playground_game_categories pgc ON g.id = pgc.game_id 
                LEFT JOIN playground_categories pc ON pgc.category_id = pc.id
                WHERE g.status = 'active'";

$params = [];
$types = "";

if ($selected_category) {
    $games_query .= " AND pgc.category_id = ?";
    $params[] = $selected_category;
    $types .= "i";
}

$games_query .= " ORDER BY g.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($games_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $games_result = $stmt->get_result();
} else {
    $games_result = $conn->query($games_query);
}

// Get category details if selected
$selected_cat_data = NULL;
if ($selected_category) {
    $cat_stmt = $conn->prepare("SELECT * FROM playground_categories WHERE id = ?");
    $cat_stmt->bind_param("i", $selected_category);
    $cat_stmt->execute();
    $selected_cat_data = $cat_stmt->get_result()->fetch_assoc();
    $cat_stmt->close();
}

// Get grade-based categories separately
$grades_query = "SELECT * FROM playground_categories WHERE is_grade_based = TRUE AND status = 'active' ORDER BY grade_level ASC";
$grades_result = $conn->query($grades_query);
$grades_array = [];
while ($grade = $grades_result->fetch_assoc()) {
    $grades_array[$grade['grade_level']] = $grade;
}
$grades_result = $conn->query($grades_query);

// Get non-grade-based categories
$other_categories_query = "SELECT * FROM playground_categories WHERE is_grade_based = FALSE AND status = 'active' ORDER BY display_order ASC";
$other_categories_result = $conn->query($other_categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playground | ICT with Dilhara</title>
    <link rel="icon" type="image/png" href="assest/logo/logo1.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assest/css/style.css">
    <style>
        :root {
            --gaming-dark: #0F172A;
            --gaming-light: #F8FAFC;
            --gaming-gray: #64748B;
            --gaming-success: #10B981;
            --gaming-danger: #EF4444;
            --gaming-blue: #0A1628;
            --gaming-cyan: #00D4FF;
            --gaming-purple: #7C3AED;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--gaming-blue);
            min-height: 100vh;
            color: var(--gaming-dark);
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Gaming Doodles Background */
        .gaming-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #0A1628 0%, #1E3A5F 50%, #0A1628 100%);
        }

        .gaming-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(124, 58, 237, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(0, 102, 255, 0.08) 0%, transparent 40%);
        }

        .doodle {
            position: absolute;
            opacity: 0.15;
            color: #00D4FF;
            font-size: 2rem;
            animation: float 20s infinite ease-in-out;
            filter: drop-shadow(0 0 10px rgba(0, 212, 255, 0.3));
        }

        .doodle:nth-child(1) { top: 10%; left: 5%; animation-delay: 0s; font-size: 2.5rem; }
        .doodle:nth-child(2) { top: 20%; left: 85%; animation-delay: -3s; font-size: 1.8rem; color: #7C3AED; }
        .doodle:nth-child(3) { top: 60%; left: 10%; animation-delay: -5s; font-size: 2.2rem; }
        .doodle:nth-child(4) { top: 80%; left: 75%; animation-delay: -8s; font-size: 2rem; color: #00D4FF; }
        .doodle:nth-child(5) { top: 40%; left: 92%; animation-delay: -2s; font-size: 1.5rem; color: #7C3AED; }
        .doodle:nth-child(6) { top: 70%; left: 3%; animation-delay: -10s; font-size: 2.3rem; }
        .doodle:nth-child(7) { top: 5%; left: 50%; animation-delay: -4s; font-size: 1.6rem; color: #00D4FF; }
        .doodle:nth-child(8) { top: 90%; left: 40%; animation-delay: -7s; font-size: 2rem; color: #7C3AED; }
        .doodle:nth-child(9) { top: 30%; left: 20%; animation-delay: -1s; font-size: 1.4rem; }
        .doodle:nth-child(10) { top: 55%; left: 60%; animation-delay: -6s; font-size: 2.1rem; color: #00D4FF; }
        .doodle:nth-child(11) { top: 15%; left: 70%; animation-delay: -9s; font-size: 1.7rem; }
        .doodle:nth-child(12) { top: 85%; left: 15%; animation-delay: -11s; font-size: 1.9rem; color: #7C3AED; }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg) scale(1);
            }
            25% {
                transform: translateY(-30px) rotate(5deg) scale(1.05);
            }
            50% {
                transform: translateY(-15px) rotate(-3deg) scale(0.98);
            }
            75% {
                transform: translateY(-40px) rotate(8deg) scale(1.02);
            }
        }

        /* Floating particles */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(0, 212, 255, 0.6);
            border-radius: 50%;
            animation: particle-float 15s infinite linear;
        }

        .particle:nth-child(13) { left: 10%; animation-delay: 0s; }
        .particle:nth-child(14) { left: 20%; animation-delay: -2s; }
        .particle:nth-child(15) { left: 30%; animation-delay: -4s; }
        .particle:nth-child(16) { left: 40%; animation-delay: -1s; }
        .particle:nth-child(17) { left: 50%; animation-delay: -3s; }
        .particle:nth-child(18) { left: 60%; animation-delay: -5s; }
        .particle:nth-child(19) { left: 70%; animation-delay: -2.5s; }
        .particle:nth-child(20) { left: 80%; animation-delay: -4.5s; }
        .particle:nth-child(21) { left: 90%; animation-delay: -1.5s; }

        @keyframes particle-float {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }

        .navbar-spacer {
            height: 80px;
        }

        .playground-wrapper {
            position: relative;
            z-index: 1;
        }

        .playground-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            position: relative;
            z-index: 1;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 212, 255, 0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
            border: 1px solid rgba(0, 212, 255, 0.2);
        }

        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gaming-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .sidebar-title i {
            color: var(--gaming-cyan);
        }

        .sidebar-section {
            margin-bottom: 2rem;
        }

        .sidebar-section:last-child {
            margin-bottom: 0;
        }

        .sidebar-section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--gaming-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        .category-list {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .category-item {
            padding: 0.8rem 1rem;
            background: var(--gaming-light);
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--gaming-dark);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .category-item:hover {
            background: rgba(0, 212, 255, 0.1);
            border-color: var(--gaming-cyan);
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 212, 255, 0.2);
        }

        .category-item.active {
            background: linear-gradient(135deg, #0066FF 0%, #00D4FF 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 16px rgba(0, 102, 255, 0.4);
        }

        .category-item i {
            font-size: 1.1rem;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.2);
        }

        .content-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--gaming-light);
        }

        .content-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .content-title h1 {
            font-size: 2rem;
            color: var(--gaming-dark);
            background: linear-gradient(135deg, #0066FF 0%, #00D4FF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .content-title i {
            font-size: 2.2rem;
            color: var(--gaming-cyan);
        }

        .description {
            color: var(--gaming-gray);
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .clear-filter {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .clear-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 2rem;
        }

        .game-card {
            background: white;
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .game-card:hover {
            box-shadow: 0 12px 32px rgba(0, 212, 255, 0.25);
            transform: translateY(-8px);
            border-color: var(--gaming-cyan);
        }

        .game-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #0A1628 0%, #1E3A5F 50%, #0066FF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 4rem;
            color: rgba(0, 212, 255, 0.4);
            position: relative;
        }

        .game-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 40%, rgba(0, 212, 255, 0.1) 50%, transparent 60%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .game-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .game-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .game-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gaming-dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .game-description {
            font-size: 0.9rem;
            color: var(--gaming-gray);
            margin-bottom: 1rem;
            flex-grow: 1;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .game-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.2rem;
            font-size: 0.85rem;
            color: var(--gaming-gray);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .difficulty-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-weight: 600;
        }

        .difficulty-easy {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
        }

        .difficulty-medium {
            background: linear-gradient(135deg, #FEF08A 0%, #FDE047 100%);
            color: #78350F;
        }

        .difficulty-hard {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #7F1D1D;
        }

        .play-button {
            background: linear-gradient(135deg, #0066FF 0%, #00D4FF 100%);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .play-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .play-button:hover::before {
            left: 100%;
        }

        .play-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 102, 255, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gaming-gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: rgba(0, 212, 255, 0.3);
        }

        .empty-state h2 {
            color: var(--gaming-dark);
            margin-bottom: 0.5rem;
        }



        @media (max-width: 1024px) {
            .playground-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .main-content {
                padding: 2rem;
            }

            .games-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1.5rem;
            }

            .doodle {
                font-size: 1.5rem !important;
            }
        }

        @media (max-width: 768px) {
            .playground-container {
                padding: 1rem;
                gap: 1rem;
            }

            .sidebar {
                grid-template-columns: repeat(2, 1fr);
            }

            .main-content {
                padding: 1.5rem;
            }

            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .content-title h1 {
                font-size: 1.5rem;
            }

            .games-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }

            .doodle:nth-child(n+7) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Gaming Background -->
    <div class="gaming-bg">
        <!-- Gaming Doodles -->
        <i class="fas fa-gamepad doodle"></i>
        <i class="fas fa-puzzle-piece doodle"></i>
        <i class="fas fa-code doodle"></i>
        <i class="fas fa-trophy doodle"></i>
        <i class="fas fa-star doodle"></i>
        <i class="fas fa-rocket doodle"></i>
        <i class="fas fa-brain doodle"></i>
        <i class="fas fa-dice doodle"></i>
        <i class="fas fa-chess doodle"></i>
        <i class="fas fa-keyboard doodle"></i>
        <i class="fas fa-lightbulb doodle"></i>
        <i class="fas fa-bolt doodle"></i>
        <!-- Floating Particles -->
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="playground-wrapper">
    <?php include 'navbar.php'; ?>
    
    <div class="navbar-spacer"></div>

    <div class="playground-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-title">
                <i class="fas fa-filter"></i> Categories
            </div>

            <?php if ($grades_array): ?>
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Grade Levels</div>
                    <div class="category-list">
                        <?php foreach ($grades_array as $grade_level => $grade): ?>
                            <a href="?category=<?php echo $grade['id']; ?>" 
                               class="category-item <?php echo ($selected_category == $grade['id']) ? 'active' : ''; ?>">
                                <i class="fas <?php echo htmlspecialchars($grade['icon']); ?>"></i>
                                <span>Grade <?php echo $grade_level; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($other_categories_result->num_rows > 0): ?>
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Other Categories</div>
                    <div class="category-list">
                        <?php while ($cat = $other_categories_result->fetch_assoc()): ?>
                            <a href="?category=<?php echo $cat['id']; ?>" 
                               class="category-item <?php echo ($selected_category == $cat['id']) ? 'active' : ''; ?>">
                                <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                <span><?php echo htmlspecialchars($cat['name']); ?></span>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($selected_category): ?>
                <div class="sidebar-section">
                    <a href="playground.php" class="clear-filter">
                        <i class="fas fa-redo"></i> Clear Filter
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <div class="content-title">
                    <?php if ($selected_cat_data): ?>
                        <i class="fas <?php echo htmlspecialchars($selected_cat_data['icon']); ?>"></i>
                        <div>
                            <h1><?php echo htmlspecialchars($selected_cat_data['name']); ?></h1>
                            <p class="description"><?php echo htmlspecialchars($selected_cat_data['description']); ?></p>
                        </div>
                    <?php else: ?>
                        <i class="fas fa-gamepad"></i>
                        <div>
                            <h1>Game Playground</h1>
                            <p class="description">Select a category to explore fun games and quizzes</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="games-grid">
                <?php if ($games_result->num_rows > 0): ?>
                    <?php while ($game = $games_result->fetch_assoc()): ?>
                        <div class="game-card">
                            <div class="game-image">
                                <?php if ($game['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($game['image_path']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>">
                                <?php elseif ($game['image_link']): ?>
                                    <img src="<?php echo htmlspecialchars($game['image_link']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-puzzle-piece"></i>
                                <?php endif; ?>
                            </div>
                            <div class="game-body">
                                <h3 class="game-title"><?php echo htmlspecialchars($game['title']); ?></h3>
                                <p class="game-description"><?php echo htmlspecialchars($game['description']); ?></p>
                                
                                <div class="game-meta">
                                    <span class="difficulty-badge difficulty-<?php echo $game['difficulty_level']; ?>">
                                        <?php echo ucfirst($game['difficulty_level']); ?>
                                    </span>
                                    <?php if ($game['recommended_age']): ?>
                                        <span class="meta-item">
                                            <i class="fas fa-birthday-cake"></i>
                                            <?php echo $game['recommended_age']; ?>+
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($game['game_type'] === 'link'): ?>
                                    <a href="<?php echo htmlspecialchars($game['game_file_path']); ?>" 
                                       target="_blank" 
                                       class="play-button">
                                        <i class="fas fa-play"></i> Play Now
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($game['game_file_path']); ?>" 
                                       class="play-button">
                                        <i class="fas fa-play"></i> Play Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-inbox"></i>
                        <h2>No Games Found</h2>
                        <p><?php echo $selected_category ? 'No games in this category yet.' : 'Select a category to see games.'; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    </div> <!-- Close playground-wrapper -->
</body>
</html>
