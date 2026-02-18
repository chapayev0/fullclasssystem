<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Delete Actions
if (isset($_GET['delete_inquiry'])) {
    $id = intval($_GET['delete_inquiry']);
    $conn->query("DELETE FROM class_inquiries WHERE id = $id");
    header("Location: admin_inbox.php?tab=inquiries");
    exit();
}

if (isset($_GET['delete_thread'])) {
    $student_id = intval($_GET['delete_thread']);
    // Delete all messages between admin and this student
    $conn->query("DELETE FROM messages WHERE sender_id = $student_id OR receiver_id = $student_id");
    header("Location: admin_inbox.php?tab=messages");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'inquiries';

// Fetch inquiries
$inquiries_sql = "SELECT * FROM class_inquiries ORDER BY created_at DESC";
$inquiries_res = $conn->query($inquiries_sql);

// Fetch Student Messages Threads
// Group by student (other party). We want the latest message details.
// This is a bit complex in MySQL without window functions if on old versions, but let's try a correlated subquery or just group by.
// Better: SELECT DISTINCT user_id... but we want the last message.
// Strategy: Get all unique conversation partners.
$threads = [];
$thread_sql = "
    SELECT 
        u.id as student_id, 
        s.first_name, 
        s.last_name,
        (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_activity,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM users u
    JOIN students s ON u.id = s.user_id
    JOIN messages m ON u.id = m.sender_id OR u.id = m.receiver_id
    WHERE u.role = 'student' AND (m.sender_id = ? OR m.receiver_id = ?)
    GROUP BY u.id
    ORDER BY last_activity DESC
";

// We need admin ID.
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare($thread_sql);
// We need to bind params: admin_id appears 6 times
$stmt->bind_param("iiiiiii", $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id);
$stmt->execute();
$threads_res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - Admin Dashboard | ICT with Dilhara</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --border: #E2E8F0;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .sidebar { width: 250px; background: var(--dark); color: white; min-height: 100vh; padding: 2rem; position: fixed; left: 0; top: 0; }
        .logo { font-size: 1.5rem; font-weight: 800; margin-bottom: 3rem; color: var(--primary); }
        .nav-link { display: block; color: rgba(255,255,255,0.7); text-decoration: none; padding: 1rem 0; transition: color 0.3s; }
        .nav-link:hover, .nav-link.active { color: white; font-weight: 600; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-size: 1.8rem; color: var(--dark); font-weight: 700; margin: 0; }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; font-weight: 600; color: var(--gray); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { color: var(--dark); font-size: 0.95rem; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #F1F5F9; }

        .badge-wa {
            display: inline-block;
            background: #25D366;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--gray);
            font-size: 1.1rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab {
            padding: 1rem 1.5rem;
            text-decoration: none;
            color: var(--gray);
            font-weight: 600;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab:hover:not(.active) {
            color: var(--primary);
        }

        .action-btn {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .btn-reply {
            background: #E0F2FE;
            color: #0284C7;
        }

        .btn-delete {
            background: #FEE2E2;
            color: #DC2626;
        }
        
        .unread-badge {
            background: #EF4444;
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1 class="page-title">Inbox</h1>
                <p style="color: var(--gray); margin-top: 5px;">View inquiries from students interested in joining classes.</p>
            </div>
        </div>

        <div class="tabs">
            <a href="?tab=inquiries" class="tab <?php echo $active_tab == 'inquiries' ? 'active' : ''; ?>">Class Inquiries</a>
            <a href="?tab=messages" class="tab <?php echo $active_tab == 'messages' ? 'active' : ''; ?>">Student Messages</a>
        </div>

        <?php if ($active_tab == 'inquiries'): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">Student Name</th>
                        <th style="width: 15%;">Class Preference</th>
                        <th style="width: 20%;">Contact Info</th>
                        <th style="width: 30%;">Message</th>
                        <th style="width: 15%;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($inquiries_res->num_rows > 0): ?>
                        <?php while ($row = $inquiries_res->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                </td>
                                <td>
                                    <span style="background: #E0F2FE; color: #0284C7; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($row['class_preference']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($row['contact_number']); ?></div>
                                    <?php if ($row['whatsapp_available']): ?>
                                        <div style="margin-top: 4px;">
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $row['contact_number']); ?>" target="_blank" class="badge-wa" style="text-decoration: none;">
                                                WhatsApp Chat ↗
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo !empty($row['message']) ? nl2br(htmlspecialchars($row['message'])) : '<em style="color: #94A3B8;">No message provided</em>'; ?>
                                </td>
                                <td style="color: var(--gray); font-size: 0.85rem;">
                                    <?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">No inquiries found yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Student Name</th>
                        <th style="width: 45%;">Last Message</th>
                        <th style="width: 15%;">Last Active</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($threads_res->num_rows > 0): ?>
                        <?php while ($row = $threads_res->fetch_assoc()): ?>
                            <tr style="<?php echo $row['unread_count'] > 0 ? 'background: #F8FAFC;' : ''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                                    <?php if ($row['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $row['unread_count']; ?> new</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(mb_strimwidth($row['last_message'], 0, 80, "...")); ?>
                                </td>
                                <td style="color: var(--gray); font-size: 0.85rem;">
                                    <?php echo date('M d, h:i A', strtotime($row['last_activity'])); ?>
                                </td>
                                <td>
                                    <a href="admin_chat.php?student_id=<?php echo $row['student_id']; ?>" class="action-btn btn-reply">Reply</a>
                                    <a href="?tab=messages&delete_thread=<?php echo $row['student_id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure? This will delete the entire conversation history.')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-state">No student messages yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
