<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        $conn->query("UPDATE wall_posts SET status = 'approved' WHERE id = $id");
    } elseif ($action == 'reject') {
        $conn->query("UPDATE wall_posts SET status = 'rejected' WHERE id = $id");
    } elseif ($action == 'delete') {
        $conn->query("DELETE FROM wall_posts WHERE id = $id");
    }
    header("Location: admin_wall.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

// Fetch Query based on tab
if ($active_tab == 'pending') {
    $sql = "SELECT wp.*, s.first_name, s.last_name 
            FROM wall_posts wp 
            JOIN students s ON wp.student_id = s.user_id 
            WHERE wp.status = 'pending' 
            ORDER BY wp.created_at ASC";
} else {
    $sql = "SELECT wp.*, s.first_name, s.last_name 
            FROM wall_posts wp 
            JOIN students s ON wp.student_id = s.user_id 
            ORDER BY wp.created_at DESC";
}
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wall Requests - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0066FF; --secondary: #7C3AED; --dark: #0F172A; --light: #F8FAFC; --gray: #64748B; }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } }

        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #E2E8F0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid #E2E8F0; }
        th { background: #F8FAFC; font-weight: 600; color: var(--gray); font-size: 0.9rem; text-transform: uppercase; }
        
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e2e8f0; }
        .tab { padding: 1rem 1.5rem; text-decoration: none; color: var(--gray); font-weight: 600; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab.active { color: var(--primary); border-bottom-color: var(--primary); }

        .btn-action { padding: 0.4rem 0.8rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin-right: 5px; }
        .btn-approve { background: #DCFCE7; color: #166534; }
        .btn-reject { background: #FEE2E2; color: #991b1b; }
        .btn-delete { background: #F3F4F6; color: #374151; }
        
        .status-badge { padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background: #FEF9C3; color: #854D0E; }
        .status-approved { background: #DCFCE7; color: #166534; }
        .status-rejected { background: #FEE2E2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <h1 style="margin-bottom: 2rem;">Wall of Talent Requests</h1>

        <div class="tabs">
            <a href="?tab=pending" class="tab <?php echo $active_tab == 'pending' ? 'active' : ''; ?>">Pending Approval</a>
            <a href="?tab=all" class="tab <?php echo $active_tab == 'all' ? 'active' : ''; ?>">All Posts</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">Student</th>
                        <th style="width: 30%;">Title</th>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['title']); ?>
                                    <br>
                                    <?php if ($row['image_path']): ?>
                                        <a href="<?php echo htmlspecialchars($row['image_path']); ?>" target="_blank" style="font-size: 0.8rem; color: var(--primary);">View Image</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_post.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-action" style="background: #E0F2FE; color: #0284C7;">View</a>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn-action btn-approve">Approve</a>
                                        <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn-action btn-reject">Reject</a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this post?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--gray);">No posts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
