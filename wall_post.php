<?php
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_GET['id'])) {
    header("Location: wall.php");
    exit();
}

$post_id = intval($_GET['id']);

// Fetch Post (Only approved)
$sql = "SELECT wp.*, s.first_name, s.last_name 
        FROM wall_posts wp 
        JOIN students s ON wp.student_id = s.user_id 
        WHERE wp.id = $post_id AND wp.status = 'approved'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<div style='text-align:center; padding: 4rem; font-family: sans-serif;'>Post not found or pending approval. <a href='wall.php'>Back to Wall</a></div>";
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
            --secondary: #F8F8FB;
            --accent: #E5E5E8;
            --dark: #1A1A1A;
            --light: #FFFFFF;
            --gray: #6B7280;
            --border: #E5E7EB;
        }

        body { font-family: 'Outfit', sans-serif; background: var(--secondary); margin: 0; color: var(--dark); }



        .container { max-width: 900px; margin: 8rem auto 3rem; padding: 0 2rem; }

        .back-link { display: inline-block; margin-bottom: 2rem; color: var(--gray); text-decoration: none; font-weight: 600; }
        .back-link:hover { color: var(--primary); }

        .post-article { background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        
        .post-header { margin-bottom: 2rem; text-align: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 2rem; }
        .post-title { font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.2; }
        .post-meta { color: var(--gray); font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        .author { color: var(--primary); }

        .featured-image-container { margin: 2rem 0; text-align: center; }
        .featured-image { max-width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        .post-content { font-size: 1.1rem; line-height: 1.8; color: #334155; }
        .post-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 1.5rem 0; }
        
        @media (max-width: 768px) {
            .post-article { padding: 1.5rem; }
            .post-title { font-size: 2rem; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container">
        <a href="wall.php" class="back-link">← Back to Wall</a>
        
        <article class="post-article">
            <header class="post-header">
                <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="post-meta">
                    By <span class="author"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></span>
                    <span style="margin: 0 10px;">•</span>
                    <?php echo date('F d, Y', strtotime($post['created_at'])); ?>
                </div>
            </header>

            <?php if ($post['image_path']): ?>
                <div class="featured-image-container">
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Featured Image" class="featured-image">
                </div>
            <?php endif; ?>

            <div class="post-content">
                <?php echo sanitize_html($post['content']); ?>
            </div>
        </article>
    </div>

</body>
</html>
