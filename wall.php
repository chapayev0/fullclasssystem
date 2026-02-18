<?php
include 'db_connect.php';

// Fetch Approved Posts
$sql = "SELECT wp.*, s.first_name, s.last_name 
        FROM wall_posts wp 
        JOIN students s ON wp.student_id = s.user_id 
        WHERE wp.status = 'approved' 
        ORDER BY wp.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wall of Talent | ICT with Dilhara</title>
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



        .container {
            max-width: 1200px;
            margin: 8rem auto 3rem; /* Increased top margin for fixed navbar */
            display: grid;
            grid-template-columns: 2fr 1fr; /* Two columns: Feed and Sidebar */
            gap: 3rem;
            padding: 0 2rem;
        }

        @media (max-width: 900px) {
            .container { grid-template-columns: 1fr; }
        }

        .page-header { grid-column: 1 / -1; margin-bottom: 2rem; text-align: center; }
        .page-title { font-size: 3rem; font-weight: 800; margin-bottom: 0.5rem; }
        .page-subtitle { color: var(--gray); font-size: 1.2rem; }

        /* Post Card */
        .wall-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            transition: transform 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .wall-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }

        .card-image {
            width: 100%;
            height: 250px;
            background: #f1f5f9;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-image img { width: 100%; height: 100%; object-fit: cover; }
        .placeholder-icon { font-size: 4rem; color: #cbd5e1; }

        .card-content { padding: 2rem; }
        
        .card-meta {
            display: flex;
            justify-content: space-between;
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        
        .card-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 1rem; line-height: 1.3; }
        
        .card-excerpt {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more { color: var(--primary); font-weight: 700; font-size: 0.95rem; }

        /* Sidebar */
        .sidebar-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        .sidebar-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 2px solid var(--accent); padding-bottom: 0.5rem; }
        
        .recent-list { list-style: none; padding: 0; }
        .recent-list li { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
        .recent-list li:last-child { border-bottom: none; margin-bottom: 0; }
        .recent-list a { text-decoration: none; color: var(--dark); font-weight: 600; display: block; line-height: 1.4; }
        .recent-list a:hover { color: var(--primary); }
        .recent-date { font-size: 0.8rem; color: var(--gray); display: block; margin-top: 0.3rem; }

    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Wall of Talent</h1>
            <p class="page-subtitle">Showcasing the creativity and achievements of our students.</p>
        </div>

        <!-- Left Column: Feed -->
        <div class="feed-column">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <a href="wall_post.php?id=<?php echo $row['id']; ?>" class="wall-card">
                        <?php if ($row['image_path']): ?>
                            <div class="card-image">
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Featured Image">
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <div class="card-meta">
                                <span><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                                <span><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                            </div>
                            <h2 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h2>
                            <div class="card-excerpt">
                                <?php echo strip_tags($row['content']); ?>
                            </div>
                            <span class="read-more">Read Full Post →</span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="background: white; padding: 3rem; border-radius: 12px; text-align: center; color: var(--gray);">
                    <h2>No posts yet.</h2>
                    <p>Check back later for updates from our students!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Sidebar -->
        <aside class="sidebar-column">
            <div class="sidebar-section">
                <h3 class="sidebar-title">About the Wall</h3>
                <p style="color: #4b5563; line-height: 1.6;">
                    The Wall of Talent is a space for our students to share their projects, articles, and creative works. 
                </p>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">Join Us</h3>
                <p style="color: #4b5563; line-height: 1.6; margin-bottom: 1rem;">
                    Want to be part of our community? Join our classes today!
                </p>
                <a href="class_details.php" style="display: block; background: var(--primary); color: white; text-align: center; padding: 0.8rem; border-radius: 6px; text-decoration: none; font-weight: 700;">View Classes</a>
            </div>
        </aside>
    </div>

</body>
</html>
