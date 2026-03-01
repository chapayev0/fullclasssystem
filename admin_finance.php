<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch Current Settings
$settings = [];
$res = $conn->query("SELECT * FROM site_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$teacher_share_pct = isset($settings['teacher_share_percentage']) ? intval($settings['teacher_share_percentage']) : 80;
$institute_share_pct = 100 - $teacher_share_pct;

// Fetch Class-wise Income
$current_month = date('Y-m');
$finance_data = [];

// Query to get monthly and total income per class
$sql = "SELECT 
            c.id, 
            c.subject, 
            c.grade, 
            c.fee,
            (SELECT SUM(cc.fee) FROM fee_payments ff JOIN classes cc ON ff.class_id = cc.id WHERE ff.class_id = c.id AND ff.status = 'Paid' AND ff.month_year = '$current_month') as monthly_income,
            (SELECT SUM(cc.fee) FROM fee_payments ff JOIN classes cc ON ff.class_id = cc.id WHERE ff.class_id = c.id AND ff.status = 'Paid') as total_income
        FROM classes c
        ORDER BY c.grade ASC, c.subject ASC";

$result = $conn->query($sql);
if ($result) {
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
}

// Global Totals
$total_monthly = 0;
$total_overall = 0;
$total_teacher_all = 0;
$total_institute_all = 0;

foreach ($finance_data as $data) {
    $total_monthly += $data['monthly_income'];
    $total_overall += $data['total_income'];
    $total_teacher_all += $data['teacher_total'];
    $total_institute_all += $data['institute_total'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Overview | Admin</title>
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
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        .header { margin-bottom: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 1.2rem 1rem; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: var(--gray); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; }
        tr:hover { background: #fcfdfe; }
        
        .badge { padding: 0.3rem 0.6rem; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }
        .badge-teacher { background: #E0F2FE; color: var(--primary); }
        .badge-institute { background: #F3E8FF; color: var(--secondary); }
        
        .income-pill { font-weight: 700; color: var(--dark); }
        
        .stats-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .summary-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); border-left: 4px solid var(--primary); }
        .summary-card h4 { margin: 0; color: var(--gray); font-size: 0.85rem; text-transform: uppercase; }
        .summary-card p { margin: 0.5rem 0 0; font-size: 1.5rem; font-weight: 800; color: var(--dark); }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Finance Overview</h1>
            <p style="color: var(--gray);">Detailed income breakdown and profit sharing (Teacher: <?php echo $teacher_share_pct; ?>% | Institute: <?php echo $institute_share_pct; ?>%)</p>
        </div>

        <div class="stats-summary">
            <div class="summary-card" style="border-color: var(--success);">
                <h4>Monthly Total</h4>
                <p>Rs. <?php echo number_format($total_monthly, 2); ?></p>
            </div>
            <div class="summary-card" style="border-color: var(--primary);">
                <h4>Overall Total</h4>
                <p>Rs. <?php echo number_format($total_overall, 2); ?></p>
            </div>
            <div class="summary-card" style="border-color: var(--secondary);">
                <h4>Institute Total Part</h4>
                <p>Rs. <?php echo number_format($total_institute_all, 2); ?></p>
            </div>
            <div class="summary-card" style="border-color: #F59E0B;">
                <h4>Teachers Total Part</h4>
                <p>Rs. <?php echo number_format($total_teacher_all, 2); ?></p>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Class-wise Breakdown (<?php echo date('F Y'); ?>)</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Class / Subject</th>
                            <th>Student Fee</th>
                            <th>Monthly Earning</th>
                            <th>Total Income</th>
                            <th>Teacher Share</th>
                            <th>Institute Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($finance_data)): ?>
                            <tr><td colspan="6" style="text-align:center; color:var(--gray);">No classes found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($finance_data as $data): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color: var(--dark);"><?php echo htmlspecialchars($data['subject']); ?></div>
                                        <div style="font-size:0.8rem; color: var(--gray);">Grade <?php echo $data['grade']; ?></div>
                                    </td>
                                    <td>Rs. <?php echo number_format($data['fee'], 2); ?></td>
                                    <td class="income-pill">Rs. <?php echo number_format($data['monthly_income'], 2); ?></td>
                                    <td>Rs. <?php echo number_format($data['total_income'], 2); ?></td>
                                    <td>
                                        <div class="badge badge-teacher">Rs. <?php echo number_format($data['teacher_total'], 2); ?></div>
                                        <div style="font-size:0.7rem; color:var(--gray); margin-top:3px;">Monthly: Rs. <?php echo number_format($data['teacher_monthly'], 2); ?></div>
                                    </td>
                                    <td>
                                        <div class="badge badge-institute">Rs. <?php echo number_format($data['institute_total'], 2); ?></div>
                                        <div style="font-size:0.7rem; color:var(--gray); margin-top:3px;">Monthly: Rs. <?php echo number_format($data['institute_monthly'], 2); ?></div>
                                    </td>
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
