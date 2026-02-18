<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Fetch posts
$sql = "SELECT wp.*, s.first_name, s.last_name 
        FROM wall_posts wp 
        JOIN students s ON wp.student_id = s.user_id 
        ORDER BY wp.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wall of Talent | ICT with Dilhara</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 250px;
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-title { margin: 0; color: var(--dark); font-size: 2rem; }
        
        .btn-create {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-create:hover { transform: translateY(-2px); }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .post-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        .post-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
        
        .post-content-preview {
            padding: 1.5rem;
            flex: 1;
        }
        
        /* Attempt to show first image as thumbnail if exists, via check would be complex, let's just show text preview */
        .post-title {
            margin: 0 0 1rem 0;
            color: var(--dark);
            font-size: 1.25rem;
        }
        
        .post-excerpt {
            color: var(--gray);
            font-size: 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }

        .post-footer {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .author { font-weight: 600; color: var(--primary); }
    </style>
</head>
<body>
    <?php include 'student_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1 class="page-title">Wall of Talent</h1>
            <a href="create_post.php" class="btn-create">+ New Post</a>
        </div>

        <div class="posts-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="post-card" onclick="window.location.href='view_post.php?id=<?php echo $row['id']; ?>'">
                        <div class="post-content-preview">
                            <h3 class="post-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="post-excerpt">
                                <?php echo strip_tags($row['content']); ?>
                            </div>
                        </div>
                        <div class="post-footer">
                            <span class="author"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                            <span><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: var(--gray); font-size: 1.2rem; margin-top: 3rem;">
                    No posts yet. Be the first to showcase your talent!
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
