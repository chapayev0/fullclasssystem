<?php
include 'db_connect.php';
include 'helpers.php';

// Get filter values
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$grade_filter = isset($_GET['grade']) ? intval($_GET['grade']) : 0;
$subject_filter = isset($_GET['subject']) ? $conn->real_escape_string($_GET['subject']) : '';

// Build query
$where_clauses = [];
if (!empty($search)) {
    $where_clauses[] = "(c.subject LIKE '%$search%' OR t.name LIKE '%$search%')";
}
if ($grade_filter > 0) {
    $where_clauses[] = "c.grade = $grade_filter";
}
if (!empty($subject_filter)) {
    $where_clauses[] = "c.subject = '$subject_filter'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

$sql = "SELECT c.*, s.description as subject_desc, s.subject_logo, s.logo_emoji as subject_emoji, 
               t.id as teacher_id, t.name as teacher_name, t.image as teacher_image, t.qualifications as teacher_qual
        FROM classes c
        LEFT JOIN subjects s ON c.subject = s.name
        LEFT JOIN teachers t ON s.teacher_id = t.id" . $where_sql . "
        ORDER BY c.grade ASC, c.class_day ASC, c.start_time ASC";

$result = $conn->query($sql);
$classes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Get all subjects for filter dropdown
$subjects_result = $conn->query("SELECT name FROM subjects ORDER BY name ASC");
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
    <title>Explore Classes | ICT with Dilhara</title>
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
            grid-template-columns: 2fr 1fr 1fr auto;
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

        /* Classes Grid */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .class-card {
            background: var(--light);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .class-badge {
            background: var(--secondary);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary);
        }

        .class-icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            background: var(--secondary);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .class-details h3 { font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; }
        .class-schedule { display: flex; align-items: center; gap: 0.5rem; color: var(--gray); font-weight: 600; font-size: 0.95rem; }

        .teacher-snippet {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            margin-top: auto;
            text-decoration: none;
            color: inherit;
        }

        .teacher-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--secondary);
        }

        .teacher-meta h4 { font-weight: 700; font-size: 0.95rem; }
        .teacher-meta p { font-size: 0.8rem; color: var(--gray); }

        .btn-view {
            text-align: center;
            padding: 0.8rem;
            background: var(--secondary);
            color: var(--dark);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-view:hover { background: var(--primary); color: white; }

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
            <h1 class="page-title">Explore Our Classes</h1>
            <p class="page-subtitle">Find the perfect ICT class tailored for your grade and learning goals. Search by subject or teacher.</p>
        </header>

        <section class="filters-section">
            <form action="classes.php" method="GET" class="filters-form">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search subject or teacher..." value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                </div>
                <div class="filter-group">
                    <label>Grade</label>
                    <select name="grade" class="filter-input">
                        <option value="0">All Grades</option>
                        <?php for($i=6; $i<=13; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($grade_filter == $i) ? 'selected' : ''; ?>><?php echo format_grade($i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Subject</label>
                    <select name="subject" class="filter-input">
                        <option value="">All Subjects</option>
                        <?php foreach($all_subjects as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($subject_filter == $s) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-search">Apply Filters</button>
            </form>
        </section>

        <div class="classes-grid">
            <?php if (count($classes) > 0): ?>
                <?php foreach ($classes as $c): ?>
                    <div class="class-card">
                        <div class="class-header">
                            <div class="class-icon">
                                <?php echo $c['subject_emoji'] ? $c['subject_emoji'] : '💻'; ?>
                            </div>
                            <span class="class-badge"><?php echo format_grade($c['grade']); ?></span>
                        </div>
                        
                        <div class="class-details">
                            <h3><?php echo htmlspecialchars($c['subject']); ?></h3>
                            <div class="class-schedule">
                                <span>📅 <?php echo $c['class_day']; ?></span>
                                <span>|</span>
                                <span>⏰ <?php echo date("g:i A", strtotime($c['start_time'])); ?></span>
                            </div>
                        </div>

                        <a href="timetable.php?subject=<?php echo urlencode($c['subject']); ?>" class="btn-view">View Full Schedule</a>

                        <a href="teacher_details.php?id=<?php echo $c['teacher_id']; ?>" class="teacher-snippet">
                            <img src="<?php echo htmlspecialchars($c['teacher_image']); ?>" class="teacher-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($c['teacher_name']); ?>&background=random'">
                            <div class="teacher-meta">
                                <h4><?php echo htmlspecialchars($c['teacher_name']); ?></h4>
                                <p><?php echo htmlspecialchars($c['teacher_qual']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <h2>No classes found</h2>
                    <p>Try adjusting your search or filters to find what you're looking for.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
