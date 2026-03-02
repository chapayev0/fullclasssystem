<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    die("Teacher profile not found.");
}

$teacher_id = $teacher['id'];

// Fetch Share Settings
$settings_res = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'teacher_share_percentage'");
$teacher_share_pct = ($settings_res && $settings_res->num_rows > 0) ? intval($settings_res->fetch_assoc()['setting_value']) : 80;
$institute_share_pct = 100 - $teacher_share_pct;

// Fetch Class-wise Income for this teacher
$current_month = date('Y-m');
$finance_data = [];

$sql = "SELECT 
            c.id, 
            c.subject, 
            c.grade, 
            c.fee,
            (SELECT SUM(cc.fee) FROM fee_payments ff JOIN classes cc ON ff.class_id = cc.id WHERE ff.class_id = c.id AND ff.status = 'Paid' AND ff.month_year = '$current_month') as monthly_income,
            (SELECT SUM(cc.fee) FROM fee_payments ff JOIN classes cc ON ff.class_id = cc.id WHERE ff.class_id = c.id AND ff.status = 'Paid') as total_income
        FROM classes c
        JOIN subjects s ON c.subject = s.name
        WHERE s.teacher_id = ?
        ORDER BY c.grade ASC, c.subject ASC";

$stmt_fin = $conn->prepare($sql);
$stmt_fin->bind_param("i", $teacher_id);
$stmt_fin->execute();
$result = $stmt_fin->get_result();

while ($row = $result->fetch_assoc()) {
    $row['monthly_income'] = $row['monthly_income'] ?? 0;
    $row['total_income'] = $row['total_income'] ?? 0;
    
    // Calculations
    $row['teacher_monthly'] = ($row['monthly_income'] * $teacher_share_pct) / 100;
    $row['institute_monthly'] = ($row['monthly_income'] * $institute_share_pct) / 100;
    
    $row['teacher_total'] = ($row['total_income'] * $teacher_share_pct) / 100;
    $row['institute_total'] = ($row['total_income'] * $institute_share_pct) / 100;
    
    $finance_data[] = $row;
}

// Global Totals
$total_monthly = 0;
$total_overall = 0;
$total_teacher_all = 0;
$total_institute_all = 0;

foreach ($finance_data as $data) {
    $total_monthly += $data['teacher_monthly'];
    $total_overall += $data['teacher_total'];
    $total_institute_all += $data['institute_total'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Finance | Teacher Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --success: #10B981;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .header { margin-bottom: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 1.2rem 1rem; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: var(--gray); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; }
        
        .badge { padding: 0.3rem 0.6rem; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }
        .badge-teacher { background: #E0F2FE; color: var(--primary); }
        .badge-institute { background: #FEE2E2; color: #EF4444; }
        
        .stats-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .summary-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); border-left: 4px solid var(--primary); }
        .summary-card h4 { margin: 0; color: var(--gray); font-size: 0.85rem; text-transform: uppercase; }
        .summary-card p { margin: 0.5rem 0 0; font-size: 1.5rem; font-weight: 800; color: var(--dark); }
    </style>
</head>
<body>
    <?php include 'teacher_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>My Finance</h1>
            <p style="color: var(--gray);">Your earnings breakdown (Share: <?php echo $teacher_share_pct; ?>%)</p>
        </div>

        <div class="stats-summary">
            <div class="summary-card" style="border-color: var(--success);">
                <h4>Monthly My Share</h4>
                <p>Rs. <?php echo number_format($total_monthly, 2); ?></p>
            </div>
            <div class="summary-card" style="border-color: var(--primary);">
                <h4>Total My Share</h4>
                <p>Rs. <?php echo number_format($total_overall, 2); ?></p>
            </div>
            <div class="summary-card" style="border-color: #EF4444;">
                <h4>Total Inst. Share</h4>
                <p>Rs. <?php echo number_format($total_institute_all, 2); ?></p>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Class Breakdown</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Class / Subject</th>
                            <th>Total Gross</th>
                            <th>My Share (<?php echo $teacher_share_pct; ?>%)</th>
                            <th>Inst. Share (<?php echo $institute_share_pct; ?>%)</th>
                            <th>Month Gross</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($finance_data)): ?>
                            <tr><td colspan="5" style="text-align:center; color:var(--gray);">No classes found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($finance_data as $data): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color: var(--dark);"><?php echo htmlspecialchars($data['subject']); ?></div>
                                        <div style="font-size:0.8rem; color: var(--gray);">Grade <?php echo $data['grade']; ?></div>
                                    </td>
                                    <td>Rs. <?php echo number_format($data['total_income'], 2); ?></td>
                                    <td><div class="badge badge-teacher">Rs. <?php echo number_format($data['teacher_total'], 2); ?></div></td>
                                    <td><div class="badge badge-institute">Rs. <?php echo number_format($data['institute_total'], 2); ?></div></td>
                                    <td>Rs. <?php echo number_format($data['monthly_income'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
