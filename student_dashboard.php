<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM students WHERE user_id = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    $error = "Student profile not found. Please contact admin.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | ICT with Dilhara</title>
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
        /* Sidebar styles are now in student_sidebar.php */

        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 250px; /* Matched sidebar width */
            transition: margin-left 0.3s ease;
        }

        /* Responsive handled by sidebar css mostly, but ensuring main content adapts */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
                padding-top: 5rem;
            }
        }
        
        .card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        h2 {
            margin-top: 0;
            color: var(--dark);
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        /* Responsive Profile Grid */
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .section-header {
                grid-column: span 1;
            }
            .full-width {
                grid-column: span 1;
            }
        }

        .profile-item {
            margin-bottom: 1rem;
        }
        .label {
            display: block;
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        .value {
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
        }
        .full-width {
            grid-column: span 2;
        }
        .section-header {
            grid-column: span 2;
            color: var(--primary);
            font-weight: 700;
            margin-top: 1rem;
            font-size: 1.2rem;
        }
        
        /* Load More Styles */
        .resource-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 2rem;
        }
        
        .hidden-item {
            display: none !important;
        }
        
        .load-more-btn {
            display: block;
            width: fit-content;
            margin: 2rem auto 0;
            padding: 0.8rem 2rem;
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .load-more-btn:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'student_sidebar.php'; ?>
    <div class="main-content">
        <h1 style="color: var(--dark); margin-bottom: 2rem;">Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</h1>

        <?php
        $student_grade = $student['grade'] ?? '';
        $resources = [];
        if ($student_grade) {
            // Clean grade: extract just the number (e.g., "Grade 6" -> "6")
            $clean_grade = preg_replace('/[^0-9]/', '', $student_grade);
            
            // Query: Find if student's grade is in the list OR if it's for 'All'
            $stmt = $conn->prepare("SELECT * FROM resources WHERE FIND_IN_SET(?, grade) > 0 OR grade = 'All' ORDER BY created_at DESC");
            $stmt->bind_param("s", $clean_grade);
            
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $resources[$row['category']][] = $row;
            }
            $stmt->close();
        }
        
        $categories = [
            'theory' => 'Theory',
            'tute' => 'Tutes',
            'paper' => 'Papers',
            'video' => 'Videos'
        ];
        
        $hasResources = false;
        foreach($categories as $catKey => $catName) {
            if(!empty($resources[$catKey])) {
                $hasResources = true;
                break;
            }
        }
        ?>

        <?php if(!$hasResources): ?>
             <div class="card">
                <p style="color: var(--gray); font-size: 1.1rem;">No resources available for your grade yet.</p>
             </div>
        <?php endif; ?>

        <?php foreach ($categories as $catKey => $catName): ?>
            <?php if (!empty($resources[$catKey])): ?>
                <!-- Section Container for <?php echo $catName; ?> -->
                <div class="resource-section" style="margin-bottom: 4rem; display: flow-root; border-bottom: 1px solid transparent;">
                    <h2 style="color: var(--dark); border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 2rem;"><?php echo $catName; ?></h2>
                    <div class="resource-grid" data-category="<?php echo $catKey; ?>">
                        <?php foreach ($resources[$catKey] as $resource): ?>
                            <div class="card resource-card" style="padding: 1.5rem; display: flex; flex-direction: column; height: 100%;">
                                <div style="flex: 1;">

                                    <span style="background: var(--secondary); color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                        Grade <?php echo htmlspecialchars($resource['grade']); ?>
                                    </span>
                                    <h3 style="margin: 1rem 0 0.5rem 0; color: var(--dark); font-size: 1.3rem; word-wrap: break-word; overflow-wrap: break-word;">
                                        <?php if(!empty($resource['lesson_number'])): ?>
                                            <span style="font-size: 0.9rem; color: var(--primary); display: block; margin-bottom: 0.2rem;">
                                                <?php 
                                                    $ln = $resource['lesson_number'];
                                                    if ($catKey === 'theory' && stripos($ln, 'Lesson') === false) {
                                                        $ln = 'Lesson ' . $ln;
                                                    }
                                                    echo htmlspecialchars($ln); 
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($resource['title']); ?>
                                    </h3>

                                </div>
                                
                                <?php if ($catKey === 'video'): ?>
                                    <div style="margin-top:auto; display:flex; gap:10px;">
                                        <a href="video_player.php?id=<?php echo $resource['id']; ?>" target="_blank" 
                                           style="flex:1; text-align: center; background: var(--primary); color: white; text-decoration: none; padding: 0.8rem; border-radius: 8px; font-weight: 600; transition: background 0.2s;">
                                            Play
                                        </a>
                                        <?php if ($resource['allow_download'] && $resource['video_type'] === 'file'): ?>
                                            <a href="<?php echo htmlspecialchars($resource['filepath']); ?>" download
                                               style="flex:1; text-align: center; background: #ea580c; color: white; text-decoration: none; padding: 0.8rem; border-radius: 8px; font-weight: 600; transition: background 0.2s;">
                                                Download
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($resource['filepath']); ?>" target="_blank" 
                                       style="display: inline-block; text-align: center; background: var(--primary); color: white; text-decoration: none; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 600; margin-top: auto; transition: background 0.2s;">
                                        Open
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Load More Button Placeholder -->
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ITEMS_PER_PAGE = 6;
            const sections = document.querySelectorAll('.resource-section');

            sections.forEach(section => {
                const grid = section.querySelector('.resource-grid');
                const cards = grid.querySelectorAll('.resource-card');
                
                if (cards.length > ITEMS_PER_PAGE) {
                    // Hide items beyond limit
                    cards.forEach((card, index) => {
                        if (index >= ITEMS_PER_PAGE) {
                            card.classList.add('hidden-item');
                        }
                    });

                    // Create Load More button
                    const btn = document.createElement('button');
                    btn.innerText = 'Load More';
                    btn.className = 'load-more-btn';
                    
                    btn.addEventListener('click', function() {
                        const hidden = grid.querySelectorAll('.hidden-item');
                        let count = 0;
                        hidden.forEach(item => {
                            if (count < ITEMS_PER_PAGE) {
                                item.classList.remove('hidden-item');
                                count++;
                            }
                        });
                        
                        // Hide button if no more hidden items
                        if (grid.querySelectorAll('.hidden-item').length === 0) {
                            btn.style.display = 'none';
                        }
                    });
                    
                    section.appendChild(btn);
                }
            });
        });
    </script>
</body>
</html>
