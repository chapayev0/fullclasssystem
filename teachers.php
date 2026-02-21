<?php
include 'db_connect.php';

// Get filter values
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$subject_filter = isset($_GET['subject']) ? $conn->real_escape_string($_GET['subject']) : '';

// Build query
$where_clauses = ["status = 'active'"];
if (!empty($search)) {
    $where_clauses[] = "(name LIKE '%$search%' OR qualifications LIKE '%$search%' OR bio LIKE '%$search%')";
}

// Subquery or Join for subject filter if needed
if (!empty($subject_filter)) {
    $where_clauses[] = "id IN (SELECT teacher_id FROM subjects WHERE name = '$subject_filter')";
}

$where_sql = " WHERE " . implode(" AND ", $where_clauses);

$sql = "SELECT * FROM teachers" . $where_sql . " ORDER BY created_at ASC";

$result = $conn->query($sql);
$teachers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Get all subjects for filter dropdown
$subjects_result = $conn->query("SELECT DISTINCT name FROM subjects ORDER BY name ASC");
$all_subjects = [];
if ($subjects_result) {
    while ($s = $subjects_result->fetch_assoc()) {
        $all_subjects[] = $s['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Teachers | ICT with Dilhara</title>
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
            --shadow: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--secondary); color: var(--dark); line-height: 1.6; }

        .container { max-width: 1200px; margin: 0 auto; padding: 120px 2rem 5rem; }

        /* Page Header */
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem; }
        .page-subtitle { font-size: 1.1rem; color: var(--gray); max-width: 600px; margin: 0 auto; }

        /* Filters Section */
        .filters-section {
            background: var(--light);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 3rem;
            border: 1px solid var(--border);
        }

        .filters-form {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 1.5rem;
            align-items: end;
        }

        .filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .filter-group label { font-weight: 700; font-size: 0.9rem; color: var(--dark); }
        
        .filter-input {
            padding: 0.8rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.95rem;
            background: var(--gray-light);
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(0,0,0,0.05);
        }

        .btn-search {
            background: var(--primary);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-search:hover { background: var(--primary-hover); transform: translateY(-2px); }

        /* Teachers Grid */
        .teachers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .teacher-card {
            background: var(--light);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .teacher-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .teacher-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.5rem;
            border: 4px solid var(--secondary);
            transition: all 0.3s ease;
        }

        .teacher-card:hover .teacher-image {
            border-color: var(--primary);
            transform: scale(1.05);
        }

        .teacher-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .teacher-qual {
            font-size: 0.95rem;
            color: var(--gray);
            font-style: italic;
            margin-bottom: 1rem;
            min-height: 2.8rem;
        }

        .teacher-bio {
            font-size: 0.9rem;
            color: #444;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.6;
        }

        .teacher-contact-row {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            width: 100%;
        }

        .contact-icon {
            font-size: 1.2rem;
            color: var(--gray);
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .contact-icon:hover {
            color: var(--primary);
            transform: scale(1.2);
        }

        .btn-profile {
            margin-top: 1.5rem;
            width: 100%;
            padding: 0.8rem;
            background: var(--secondary);
            color: var(--dark);
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .teacher-card:hover .btn-profile {
            background: var(--primary);
            color: white;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 5rem;
            background: var(--light);
            border-radius: 20px;
            border: 1px dashed var(--gray);
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .filters-form { grid-template-columns: 1fr; }
            .page-title { font-size: 2.2rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <header class="page-header">
            <h1 class="page-title">Our Expert Teachers</h1>
            <p class="page-subtitle">Meet our team of dedicated educators committed to your academic excellence and personal growth.</p>
        </header>

        <section class="filters-section">
            <form action="teachers.php" method="GET" class="filters-form">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search by name or keywords..." value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                </div>
                <div class="filter-group">
                    <label>Subject Specialist</label>
                    <select name="subject" class="filter-input">
                        <option value="">All Specialists</option>
                        <?php foreach($all_subjects as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($subject_filter == $s) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-search">Search Teachers</button>
            </form>
        </section>

        <div class="teachers-grid">
            <?php if (count($teachers) > 0): ?>
                <?php foreach ($teachers as $t): ?>
                    <a href="teacher_details.php?id=<?php echo $t['id']; ?>" class="teacher-card">
                        <img src="<?php echo htmlspecialchars($t['image']); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>" class="teacher-image" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($t['name']); ?>&background=random'">
                        
                        <h3 class="teacher-name"><?php echo htmlspecialchars($t['name']); ?></h3>
                        <p class="teacher-qual"><?php echo htmlspecialchars($t['qualifications']); ?></p>
                        
                        <p class="teacher-bio"><?php echo strip_tags($t['bio']); ?></p>

                        <div class="btn-profile">View Full Profile</div>

                        <div class="teacher-contact-row" onclick="event.preventDefault();">
                            <?php if(!empty($t['phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($t['phone']); ?>" class="contact-icon" title="Call">📞</a>
                            <?php endif; ?>
                            
                            <?php if(!empty($t['whatsapp'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $t['whatsapp']); ?>" target="_blank" class="contact-icon" title="WhatsApp">💬</a>
                            <?php endif; ?>
                            
                            <?php if(!empty($t['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($t['email']); ?>" class="contact-icon" title="Email">📧</a>
                            <?php endif; ?>

                            <?php if(!empty($t['website'])): ?>
                                <a href="<?php echo htmlspecialchars($t['website']); ?>" target="_blank" class="contact-icon" title="Website">🌐</a>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <h2>No teachers found</h2>
                    <p>Try adjusting your keywords or filters to find our expert instructors.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
