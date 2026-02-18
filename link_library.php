<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$links = [];

// Get student grade
$sql = "SELECT grade FROM students WHERE user_id = '$user_id'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $raw_grade = $student['grade']; 
    
    // Clean grade
    $clean_grade = preg_replace('/[^0-9]/', '', $raw_grade);
    
    // Fetch Link Library resources
    $grade_variants = [$clean_grade, 'g' . $clean_grade, 'All'];
    $placeholders = implode(',', array_fill(0, count($grade_variants), '?'));
    $types = str_repeat('s', count($grade_variants)) . 's'; // grades + category
    
    // Append 'link_library' to params
    $params = $grade_variants;
    $params[] = 'link_library';

    $stmt = $conn->prepare("SELECT * FROM resources WHERE grade IN ($placeholders) AND category = ? ORDER BY created_at DESC");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $links[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Library | ICT with Dilhara</title>
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
        /* Sidebar styles handled by include, but we need layout styles */
        .sidebar {
            width: 250px;
            background: var(--dark);
            color: white;
            min-height: 100vh;
            padding: 2rem;
            position: fixed;
            left: 0;
            top: 0;
        }
        /* ... existing sidebar styles ... */
        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 3rem;
            color: var(--primary);
        }
        .nav-link {
            display: block;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 1rem 0;
            transition: color 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            font-weight: 600;
        }
        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 290px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
                padding-top: 5rem;
            }
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        h1 {
            color: var(--dark);
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            margin-top: 0;
        }
        
    </style>
</head>
<body>
    <?php include 'student_sidebar.php'; ?>

    <div class="main-content">
        <h1>Link Library</h1>
        
        <div class="card" style="padding: 1rem; overflow-x: auto;">
            <?php if (empty($links)): ?>
                <p style="color: var(--gray); text-align: center; padding: 2rem;">No links found for your grade.</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #f1f5f9;">
                            <th style="text-align: left; padding: 1rem; color: var(--gray);">Link Details</th>
                            <th style="text-align: left; padding: 1rem; color: var(--gray);">Description</th>
                            <th style="text-align: left; padding: 1rem; color: var(--gray);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $link): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600; color: var(--dark); font-size: 1.1rem;"><?php echo htmlspecialchars($link['title']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--primary); margin-top: 4px;">
                                        <span style="background: #eff6ff; padding: 2px 8px; border-radius: 4px;">Grade <?php echo htmlspecialchars($link['grade']); ?></span>
                                    </div>
                                </td>
                                <td style="padding: 1rem; color: #64748B; max-width: 300px;">
                                    <?php echo htmlspecialchars($link['description'] ?? ''); ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <a href="<?php echo htmlspecialchars($link['filepath']); ?>" target="_blank" 
                                           style="background: var(--primary); color: white; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                            <span>Open</span>
                                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($link['filepath']); ?>')" 
                                                style="background: white; color: var(--gray); border: 1px solid #e2e8f0; padding: 0.6rem; border-radius: 8px; cursor: pointer; transition: all 0.2s;" title="Copy Link">
                                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>



    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Could show a toast here
                alert("Link copied to clipboard!");
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
    </script>
</body>
</html>
