<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'mark';

// Handle Attendance Marking (Existing Logic)
$selected_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$selected_date = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $class_id = intval($_POST['class_id']);
    $date = $_POST['date'];
    $month_year = date('Y-m', strtotime($date));
    $attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : [];
    $fee_data = isset($_POST['fees_paid']) ? $_POST['fees_paid'] : [];

    $enrollment_query = $conn->prepare("SELECT student_id FROM student_enrollments WHERE class_id = ?");
    $enrollment_query->bind_param("i", $class_id);
    $enrollment_query->execute();
    $enrolled_students = $enrollment_query->get_result();
    
    $conn->begin_transaction();
    try {
        while ($enrollment = $enrolled_students->fetch_assoc()) {
            $s_id = $enrollment['student_id'];
            
            // Handle Attendance
            $status = isset($attendance_data[$s_id]) ? 'Present' : 'Absent';
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, date, status) 
                                   VALUES (?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE status = VALUES(status), marked_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("iiss", $s_id, $class_id, $date, $status);
            $stmt->execute();

            // Handle Fee Payment (Store for the specific month of the selected date)
            if (isset($fee_data[$s_id])) {
                $f_stmt = $conn->prepare("INSERT INTO fee_payments (student_id, class_id, month_year, status) 
                                         VALUES (?, ?, ?, 'Paid') 
                                         ON DUPLICATE KEY UPDATE status = 'Paid', paid_at = CURRENT_TIMESTAMP");
                $f_stmt->bind_param("iis", $s_id, $class_id, $month_year);
                $f_stmt->execute();
            } else {
                // If checkbox is unchecked, we mark as Unpaid for that month
                $f_stmt = $conn->prepare("INSERT INTO fee_payments (student_id, class_id, month_year, status) 
                                         VALUES (?, ?, ?, 'Unpaid') 
                                         ON DUPLICATE KEY UPDATE status = 'Unpaid'");
                $f_stmt->bind_param("iis", $s_id, $class_id, $month_year);
                $f_stmt->execute();
            }
        }
        $conn->commit();
        $success_msg = "Attendance and fees updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Error moving data: " . $e->getMessage();
    }
}

// Handle Attendance Records (New Logic)
$record_class = isset($_GET['record_class']) ? intval($_GET['record_class']) : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$records = [];
$stats = ['present' => 0, 'absent' => 0, 'total' => 0];

if ($active_tab === 'records' && ($record_class > 0 || !empty($start_date))) {
    $r_sql = "SELECT a.*, s.first_name, s.last_name, u.username, c.subject, c.grade, fp.status as fee_status 
              FROM attendance a 
              JOIN students s ON a.student_id = s.id 
              JOIN users u ON s.user_id = u.id
              JOIN classes c ON a.class_id = c.id 
              LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.class_id = c.id AND fp.month_year = DATE_FORMAT(a.date, '%Y-%m')
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($record_class > 0) {
        $r_sql .= " AND a.class_id = ?";
        $params[] = $record_class;
        $types .= "i";
    }
    if (!empty($start_date) && !empty($end_date)) {
        $r_sql .= " AND a.date BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }
    
    $r_sql .= " ORDER BY a.date DESC, s.first_name ASC";
    $r_stmt = $conn->prepare($r_sql);
    if (!empty($params)) {
        $r_stmt->bind_param($types, ...$params);
    }
    $r_stmt->execute();
    $r_result = $r_stmt->get_result();
    while ($row = $r_result->fetch_assoc()) {
        $records[] = $row;
        if ($row['status'] === 'Present') $stats['present']++;
        else $stats['absent']++;
        $stats['total']++;
    }
}

// Shared: Fetch classes for dropdowns
$classes = [];
$c_result = $conn->query("SELECT id, grade, subject FROM classes ORDER BY grade ASC");
if ($c_result) {
    while ($row = $c_result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Fetch students for Mark Attendance tab
$students = [];
if ($active_tab === 'mark' && $selected_class) {
    $month_year = date('Y-m', strtotime($selected_date));
    $s_sql = "SELECT s.id, s.first_name, s.last_name, u.username, a.status, fp.status as fee_status 
              FROM student_enrollments se 
              JOIN students s ON se.student_id = s.id 
              JOIN users u ON s.user_id = u.id
              LEFT JOIN attendance a ON a.student_id = s.id AND a.class_id = se.class_id AND a.date = ? 
              LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.class_id = se.class_id AND fp.month_year = ?
              WHERE se.class_id = ? 
              ORDER BY s.first_name ASC";
    $s_stmt = $conn->prepare($s_sql);
    $s_stmt->bind_param("ssi", $selected_date, $month_year, $selected_class);
    $s_stmt->execute();
    $students_result = $s_stmt->get_result();
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --danger: #EF4444;
            --success: #10B981;
            --border: #E2E8F0;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        .header { margin-bottom: 2rem; }
        
        /* Tabs */
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1px; }
        .tab {
            padding: 1rem 2rem;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            text-decoration: none;
        }
        .tab:hover { color: var(--primary); }
        .tab.active { color: var(--primary); border-bottom-color: var(--primary); }

        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark); }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; }
        
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-success { background: var(--success); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border); }
        th { color: var(--gray); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        
        .status-badge { padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.8rem; font-weight: 700; }
        .status-present { background: #DCFCE7; color: #166534; }
        .status-absent { background: #FEE2E2; color: #991B1B; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid var(--border); text-align: center; }
        .stat-value { font-size: 1.8rem; font-weight: 800; color: var(--dark); display: block; }
        .stat-label { color: var(--gray); font-size: 0.9rem; font-weight: 600; }

        .attendance-checkbox { width: 22px; height: 22px; cursor: pointer; accent-color: var(--success); }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }

        .qr-scanner-btn { background: var(--dark); color: white; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 1.5rem; }
        #reader { width: 100%; max-width: 500px; margin: 0 auto; display: none; }

        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Attendance Management</h1>
            <p style="color: var(--gray);">Track student participation and view historical records.</p>
        </div>

        <div class="tabs">
            <a href="admin_attendance.php?tab=mark" class="tab <?php echo ($active_tab === 'mark') ? 'active' : ''; ?>">Mark Attendance</a>
            <a href="admin_attendance.php?tab=records" class="tab <?php echo ($active_tab === 'records') ? 'active' : ''; ?>">Attendance Records</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Mark Attendance Tab Content -->
        <?php if ($active_tab === 'mark'): ?>
            <div class="card">
                <form method="GET" class="form-grid">
                    <input type="hidden" name="tab" value="mark">
                    <div class="form-group">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($selected_class == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo format_grade($c['grade']) . ' - ' . htmlspecialchars($c['subject']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Select Date</label>
                        <input type="date" name="attendance_date" class="form-control" value="<?php echo $selected_date; ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn"><i class="fas fa-search"></i> Load Student List</button>
                    </div>
                </form>
            </div>

            <?php if ($selected_class): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0;">Mark Attendance</h3>
                        <button id="qrToggle" class="qr-scanner-btn"><i class="fas fa-qrcode"></i> QR Scanner Mode</button>
                    </div>

                    <div id="reader"></div>

                    <form method="POST">
                        <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                        <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                        
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">
                                        <input type="checkbox" id="selectAll" style="width: 18px; height: 18px; cursor: pointer;">
                                    </th>
                                    <th>Student Name</th>
                                    <th>Student ID</th>
                                    <th>Attendance</th>
                                    <th style="text-align: center;">Fee Paid (<?php echo date('M', strtotime($selected_date)); ?>)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="4" style="text-align: center; color: var(--gray);">No students enrolled in this class.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td><input type="checkbox" name="attendance[<?php echo $s['id']; ?>]" class="attendance-checkbox" value="Present" <?php echo ($s['status'] === 'Present') ? 'checked' : ''; ?> id="stu_<?php echo $s['username']; ?>"></td>
                                            <td><div style="font-weight: 600;"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></div></td>
                                            <td><code style="background: var(--light); padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($s['username']); ?></code></td>
                                            <td>
                                                <?php if ($s['status'] === 'Present'): ?>
                                                    <span style="color: var(--success); font-weight: 600;"><i class="fas fa-check-circle"></i> Present</span>
                                                <?php else: ?>
                                                    <span style="color: var(--gray); font-size: 0.9rem;">Not Marked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="fees_paid[<?php echo $s['id']; ?>]" 
                                                       style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--primary);" 
                                                       value="Paid"
                                                       <?php echo ($s['fee_status'] === 'Paid') ? 'checked' : ''; ?>>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
<?php endif; ?>
                            </tbody>
                        </table>
                        <div style="margin-top: 2rem;"><button type="submit" name="save_attendance" class="btn btn-success"><i class="fas fa-save"></i> Save Attendance & Fee Status</button></div>
                    </form>
                </div>
            <?php endif; ?>

        <!-- Attendance Records Tab Content -->
        <?php else: ?>
            <div class="card">
                <form method="GET" class="form-grid">
                    <input type="hidden" name="tab" value="records">
                    <div class="form-group">
                        <label class="form-label">Filter by Class</label>
                        <select name="record_class" class="form-control">
                            <option value="0">All Classes</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($record_class == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo format_grade($c['grade']) . ' - ' . htmlspecialchars($c['subject']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">From Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">To Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn"><i class="fas fa-filter"></i> Apply Filters</button>
                    </div>
                </form>
            </div>

            <?php if (!empty($records)): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-value"><?php echo $stats['total']; ?></span>
                        <span class="stat-label">Total Records</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value" style="color: var(--success);"><?php echo $stats['present']; ?></span>
                        <span class="stat-label">Students Present</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value" style="color: var(--danger);"><?php echo $stats['absent']; ?></span>
                        <span class="stat-label">Students Absent</span>
                    </div>
                </div>

                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Attendance</th>
                                <th>Fee Status</th>
                                <th>Marked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td><strong><?php echo date("M d, Y", strtotime($r['date'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?> (<?php echo htmlspecialchars($r['username']); ?>)</td>
                                    <td><?php echo format_grade($r['grade']) . ' - ' . htmlspecialchars($r['subject']); ?></td>
                                    <td><span class="status-badge <?php echo ($r['status'] === 'Present') ? 'status-present' : 'status-absent'; ?>"><?php echo $r['status']; ?></span></td>
                                    <td>
                                        <span class="status-badge <?php echo ($r['fee_status'] === 'Paid') ? 'status-present' : 'status-absent'; ?>" style="font-size: 0.75rem;">
                                            <i class="fas <?php echo ($r['fee_status'] === 'Paid') ? 'fa-check' : 'fa-times'; ?>"></i> 
                                            <?php echo $r['month_year']; ?>: <?php echo ($r['fee_status'] === 'Paid') ? 'Paid' : 'Unpaid'; ?>
                                        </span>
                                    </td>
                                    <td><small style="color: var(--gray);"><?php echo date("g:i A", strtotime($r['marked_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($record_class > 0): ?>
                <div class="stat-card" style="padding: 3rem;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                    <p style="color: var(--gray);">No attendance records found for the selected filters.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Scripts (QRScanner logic remains same) -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        const selectAll = document.getElementById('selectAll');
        if (selectAll) { selectAll.addEventListener('change', function() { 
            document.querySelectorAll('.attendance-checkbox').forEach(cb => cb.checked = this.checked); 
        }); }
        const qrToggle = document.getElementById('qrToggle');
        const reader = document.getElementById('reader');
        let html5QrcodeScanner = null;
        if (qrToggle) { qrToggle.addEventListener('click', function() {
            if (reader.style.display === 'none' || reader.style.display === '') {
                reader.style.display = 'block'; startScanner();
                this.innerHTML = '<i class="fas fa-times"></i> Close Scanner'; this.style.background = 'var(--danger)';
            } else {
                reader.style.display = 'none'; if (html5QrcodeScanner) html5QrcodeScanner.clear();
                this.innerHTML = '<i class="fas fa-qrcode"></i> QR Scanner Mode'; this.style.background = 'var(--dark)';
            }
        }); }
        function startScanner() { html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 }); html5QrcodeScanner.render(onScanSuccess); }
        function onScanSuccess(decodedText, decodedResult) {
            const checkbox = document.getElementById('stu_' + decodedText);
            if (checkbox) { if (!checkbox.checked) { checkbox.checked = true; checkbox.closest('tr').style.backgroundColor = '#ecfdf5'; } }
            else { alert('Student with ID ' + decodedText + ' not found.'); }
        }
    </script>
</body>
</html>
