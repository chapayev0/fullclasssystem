<?php
session_start();
include 'db_connect.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';
$user_id = $_SESSION['user_id'];

// Handle Add Game
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $difficulty_level = $_POST['difficulty_level'];
    $recommended_age = intval($_POST['recommended_age']) ?? NULL;
    $game_type = $_POST['game_type'];
    $game_file_path = '';
    $image_path = '';
    $image_link = '';

    if (empty($title)) {
        $error_msg = "Game title is required.";
    } else {
        // Handle image upload or link
        if (!empty($_POST['image_link'])) {
            $image_link = trim($_POST['image_link']);
        } elseif (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'assest/images/playground/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $allowed)) {
                $new_filename = uniqid('game_') . '.' . $file_ext;
                $image_path = $upload_dir . $new_filename;
                if (!move_uploaded_file($_FILES['image_upload']['tmp_name'], $image_path)) {
                    $error_msg = "Failed to upload image.";
                    $image_path = '';
                }
            } else {
                $error_msg = "Invalid image format. Allowed: JPG, PNG, GIF, WebP";
            }
        }

        // Handle game file
        if (empty($error_msg)) {
            if ($game_type === 'link') {
                $game_file_path = trim($_POST['game_link']);
                if (empty($game_file_path)) {
                    $error_msg = "Game link is required for link-type games.";
                }
            } elseif (isset($_FILES['game_file']) && $_FILES['game_file']['error'] === UPLOAD_ERR_OK) {
                $game_upload_dir = 'assest/games/';
                if (!is_dir($game_upload_dir)) {
                    mkdir($game_upload_dir, 0755, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['game_file']['name'], PATHINFO_EXTENSION));
                $allowed_game = ['html', 'php', 'zip'];
                
                if (in_array($file_ext, $allowed_game)) {
                    $new_filename = uniqid('game_') . '.' . $file_ext;
                    $game_file_path = $game_upload_dir . $new_filename;
                    if (!move_uploaded_file($_FILES['game_file']['tmp_name'], $game_file_path)) {
                        $error_msg = "Failed to upload game file.";
                        $game_file_path = '';
                    }
                } else {
                    $error_msg = "Invalid game file format. Allowed: HTML, PHP, ZIP";
                }
            } else {
                $error_msg = "Game file is required.";
            }
        }

        // Insert game if no errors
        if (empty($error_msg)) {
            $stmt = $conn->prepare("INSERT INTO playground_games (title, description, image_path, image_link, game_file_path, game_type, difficulty_level, recommended_age, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssii", $title, $description, $image_path, $image_link, $game_file_path, $game_type, $difficulty_level, $recommended_age, $user_id);
            
            if ($stmt->execute()) {
                $game_id = $stmt->insert_id;
                
                // Handle categories
                $categories = $_POST['categories'] ?? [];
                if (!empty($categories)) {
                    foreach ($categories as $cat_id) {
                        $cat_id = intval($cat_id);
                        $cat_stmt = $conn->prepare("INSERT INTO playground_game_categories (game_id, category_id) VALUES (?, ?)");
                        $cat_stmt->bind_param("ii", $game_id, $cat_id);
                        $cat_stmt->execute();
                        $cat_stmt->close();
                    }
                }
                
                $success_msg = "Game added successfully!";
            } else {
                $error_msg = "Error adding game: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle Delete Game
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game'])) {
    $game_id = intval($_POST['game_id']);
    
    // Get game files
    $stmt = $conn->prepare("SELECT image_path, game_file_path FROM playground_games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $game = $result->fetch_assoc();
    $stmt->close();
    
    if ($game) {
        // Delete from DB
        $stmt = $conn->prepare("DELETE FROM playground_games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        
        if ($stmt->execute()) {
            // Delete files
            if ($game['image_path'] && file_exists($game['image_path'])) {
                @unlink($game['image_path']);
            }
            if ($game['game_file_path'] && strpos($game['game_file_path'], 'http') !== 0 && file_exists($game['game_file_path'])) {
                @unlink($game['game_file_path']);
            }
            $success_msg = "Game deleted successfully!";
        } else {
            $error_msg = "Error deleting game: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $game_id = intval($_POST['game_id']);
    
    $stmt = $conn->prepare("SELECT status FROM playground_games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $game = $result->fetch_assoc();
    $stmt->close();
    
    if ($game) {
        $new_status = $game['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE playground_games SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $game_id);
        
        if ($stmt->execute()) {
            $success_msg = "Game status updated!";
        } else {
            $error_msg = "Error updating status: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all games
$games_query = "SELECT g.*, GROUP_CONCAT(pc.name SEPARATOR ', ') as categories FROM playground_games g 
                LEFT JOIN playground_game_categories pgc ON g.id = pgc.game_id 
                LEFT JOIN playground_categories pc ON pgc.category_id = pc.id 
                GROUP BY g.id 
                ORDER BY g.created_at DESC";
$games_result = $conn->query($games_query);

// Fetch categories for form
$categories_result = $conn->query("SELECT * FROM playground_categories WHERE status = 'active' ORDER BY display_order ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Playground Games | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
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
        }

        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--dark);
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #0052CC;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #D97706;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #FEE2E2;
            color: #7F1D1D;
            border-left: 4px solid var(--danger);
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }

        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }

        .form-section h2 {
            margin-bottom: 1.5rem;
            color: var(--dark);
            font-size: 1.3rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="text"],
        input[type="number"],
        input[type="url"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="url"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .form-buttons .btn {
            flex: 1;
        }

        .games-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            grid-column: 2;
        }

        .games-section h2 {
            margin-bottom: 1.5rem;
            color: var(--dark);
            font-size: 1.3rem;
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .game-card {
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .game-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        .game-image {
            width: 100%;
            height: 180px;
            background: var(--light);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--gray);
        }

        .game-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .game-info {
            padding: 1.2rem;
        }

        .game-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .game-meta {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.8rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .meta-badge {
            background: var(--light);
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .game-categories {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .category-tag {
            background: rgba(0, 102, 255, 0.1);
            color: var(--primary);
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
        }

        .status-active {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-inactive {
            background: #FEE2E2;
            color: #7F1D1D;
        }

        .game-actions {
            display: flex;
            gap: 0.5rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .form-info {
            background: #F0F7FF;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.2rem;
            border-left: 4px solid var(--primary);
            font-size: 0.9rem;
        }

        .form-info strong {
            display: block;
            margin-bottom: 0.4rem;
        }

        .game-type-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .game-type-btn {
            padding: 0.7rem;
            border: 2px solid #E5E7EB;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .game-type-btn.active {
            border-color: var(--primary);
            background: rgba(0, 102, 255, 0.1);
            color: var(--primary);
        }

        .image-type-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .image-type-btn {
            padding: 0.7rem;
            border: 2px solid #E5E7EB;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .image-type-btn.active {
            border-color: var(--primary);
            background: rgba(0, 102, 255, 0.1);
            color: var(--primary);
        }

        .hidden-input {
            display: none;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }

            .games-section {
                grid-column: 1;
            }

            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-puzzle-piece"></i> Manage Playground Games</h1>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <div class="container">
            <div class="form-section">
                <h2>Add New Game</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_game" value="1">

                    <div class="form-group">
                        <label for="title">Game Title *</label>
                        <input type="text" id="title" name="title" required placeholder="Enter game name">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Describe the game..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Game Type *</label>
                        <div class="game-type-toggle">
                            <button type="button" class="game-type-btn active" data-type="html">
                                <i class="fas fa-file-code"></i> File
                            </button>
                            <button type="button" class="game-type-btn" data-type="link">
                                <i class="fas fa-link"></i> Link
                            </button>
                        </div>
                        <input type="hidden" id="game_type" name="game_type" value="html">
                    </div>

                    <div id="file-input" class="form-group">
                        <label for="game_file">Game File (HTML/PHP/ZIP) *</label>
                        <input type="file" id="game_file" name="game_file" accept=".html,.php,.zip">
                    </div>

                    <div id="link-input" class="form-group hidden-input">
                        <label for="game_link">Game URL *</label>
                        <input type="url" id="game_link" name="game_link" placeholder="https://example.com/game">
                    </div>

                    <div class="form-group">
                        <label>Image Type</label>
                        <div class="image-type-toggle">
                            <button type="button" class="image-type-btn active" data-type="upload">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                            <button type="button" class="image-type-btn" data-type="link">
                                <i class="fas fa-link"></i> Link
                            </button>
                        </div>
                    </div>

                    <div id="image-upload" class="form-group">
                        <label for="image_upload">Upload Image</label>
                        <input type="file" id="image_upload" name="image_upload" accept="image/*">
                    </div>

                    <div id="image-link" class="form-group hidden-input">
                        <label for="image_link">Image URL</label>
                        <input type="url" id="image_link" name="image_link" placeholder="https://example.com/image.jpg">
                    </div>

                    <div class="form-group">
                        <label for="difficulty_level">Difficulty Level</label>
                        <select id="difficulty_level" name="difficulty_level">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="recommended_age">Recommended Age</label>
                        <input type="number" id="recommended_age" name="recommended_age" min="3" max="18" placeholder="e.g., 10">
                    </div>

                    <div class="form-group">
                        <label>Assign to Categories *</label>
                        <div class="form-info">
                            <strong>Select one or more categories:</strong>
                            Select categories to make this game visible in those sections
                        </div>
                        <?php 
                        $categories_result->data_seek(0);
                        while ($cat = $categories_result->fetch_assoc()): 
                        ?>
                            <div class="checkbox-group">
                                <input type="checkbox" id="cat_<?php echo $cat['id']; ?>" name="categories[]" value="<?php echo $cat['id']; ?>">
                                <label for="cat_<?php echo $cat['id']; ?>" style="margin-bottom: 0;">
                                    <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Game
                        </button>
                    </div>
                </form>
            </div>

            <div class="games-section">
                <h2>All Games (<?php echo $games_result->num_rows; ?>)</h2>
                
                <div class="games-grid">
                    <?php while ($game = $games_result->fetch_assoc()): ?>
                        <div class="game-card">
                            <div class="game-image">
                                <?php if ($game['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($game['image_path']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>">
                                <?php elseif ($game['image_link']): ?>
                                    <img src="<?php echo htmlspecialchars($game['image_link']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-gamepad"></i>
                                <?php endif; ?>
                            </div>
                            <div class="game-info">
                                <div class="game-title"><?php echo htmlspecialchars($game['title']); ?></div>
                                
                                <span class="status-badge status-<?php echo $game['status']; ?>">
                                    <?php echo ucfirst($game['status']); ?>
                                </span>

                                <div class="game-meta">
                                    <span class="meta-badge">
                                        <i class="fas fa-signal"></i> 
                                        <?php echo ucfirst($game['difficulty_level']); ?>
                                    </span>
                                    <?php if ($game['recommended_age']): ?>
                                        <span class="meta-badge">
                                            <i class="fas fa-birthday-cake"></i>
                                            Age <?php echo $game['recommended_age']; ?>+
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($game['categories']): ?>
                                    <div class="game-categories">
                                        <?php foreach (explode(', ', $game['categories']) as $cat): ?>
                                            <span class="category-tag"><?php echo htmlspecialchars($cat); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="game-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="btn btn-warning btn-small" title="Toggle Status">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="delete_game" value="1">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php if ($games_result->num_rows === 0): ?>
                    <div style="text-align: center; padding: 3rem; color: var(--gray);">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                        <p>No games added yet. Create your first game!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Game type toggle
        document.querySelectorAll('.game-type-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.game-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const type = this.dataset.type;
                document.getElementById('game_type').value = type;
                
                document.getElementById('file-input').classList.toggle('hidden-input', type !== 'html');
                document.getElementById('link-input').classList.toggle('hidden-input', type !== 'link');
            });
        });

        // Image type toggle
        document.querySelectorAll('.image-type-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.image-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const type = this.dataset.type;
                document.getElementById('image-upload').classList.toggle('hidden-input', type !== 'upload');
                document.getElementById('image-link').classList.toggle('hidden-input', type !== 'link');
            });
        });
    </script>
</body>
</html>
