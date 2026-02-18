<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_GET['id'])) {
    header("Location: wall_of_talent.php");
    exit();
}

$post_id = intval($_GET['id']);

$sql = "SELECT wp.*, s.first_name, s.last_name 
        FROM wall_posts wp 
        JOIN students s ON wp.student_id = s.user_id 
        WHERE wp.id = $post_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Post not found or unavailable.";
    exit();
}

$post = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> | Wall of Talent</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0066FF; --secondary: #7C3AED; --dark: #0F172A; --light: #F8FAFC; --gray: #64748B; }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        
        .post-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            padding: 3rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            color: var(--gray);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover { color: var(--primary); }

        .post-header {
            text-align: center;
            margin-bottom: 3rem;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 2rem;
        }

        .post-title { font-size: 2.5rem; color: var(--dark); margin-bottom: 1rem; line-height: 1.2; }
        .post-meta { color: var(--gray); font-size: 1rem; }
        .author-name { color: var(--primary); font-weight: 700; }

        .post-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #334155;
        }
        .post-content img { max-width: 100%; border-radius: 10px; margin: 1.5rem 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <?php include 'student_sidebar.php'; ?>
    <div class="main-content">
        <a href="wall_of_talent.php" class="back-link">← Back to Wall</a>
        
        <div class="post-container">
            <header class="post-header">
                <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="post-meta">
                    By <span class="author-name"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></span>
                    on <?php echo date('F d, Y', strtotime($post['created_at'])); ?>
                </div>
            </header>
            
            <div class="post-content">
                <?php echo sanitize_html($post['content']); ?>
            </div>
        </div>
    </div>
</body>
</html>
