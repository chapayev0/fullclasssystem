<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Handle Delete Actions
if (isset($_GET['delete_inquiry'])) {
    $id = intval($_GET['delete_inquiry']);
    $conn->query("DELETE FROM class_inquiries WHERE id = $id");
    header("Location: admin_messages.php?tab=inquiries");
    exit();
}

if (isset($_GET['delete_thread'])) {
    $partner_id = intval($_GET['delete_thread']);
    $conn->query("DELETE FROM messages WHERE (sender_id = $partner_id AND receiver_id = $admin_id) OR (sender_id = $admin_id AND receiver_id = $partner_id)");
    header("Location: admin_messages.php?tab=" . ($_GET['type'] ?? 'student_messages'));
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'inquiries';

// Handle Start New Conversation (Teacher)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_teacher_chat'])) {
    $teacher_user_id = intval($_POST['teacher_user_id']);
    if ($teacher_user_id > 0) {
        header("Location: admin_chat.php?user_id=$teacher_user_id");
        exit();
    }
}

// Handle Broadcast to All Teachers
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['broadcast_teachers'])) {
    $broadcast_msg = trim($_POST['broadcast_message']);
    if (!empty($broadcast_msg)) {
        $teachers_list = $conn->query("SELECT user_id FROM teachers WHERE user_id IS NOT NULL");
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        
        $conn->begin_transaction();
        try {
            while ($t_row = $teachers_list->fetch_assoc()) {
                $recipient = $t_row['user_id'];
                $stmt->bind_param("iis", $admin_id, $recipient, $broadcast_msg);
                $stmt->execute();
            }
            $conn->commit();
            $success_alert = "Broadcast message sent to all teachers!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Broadcast failed: " . $e->getMessage();
        }
        $stmt->close();
    }
}

// Fetch inquiries
$inquiries_sql = "SELECT * FROM class_inquiries ORDER BY created_at DESC";
$inquiries_res = $conn->query($inquiries_sql);

// Function to fetch threads based on role
function getThreads($conn, $admin_id, $role) {
    $sql = "
        SELECT 
            u.id as partner_id, 
            COALESCE(s.first_name, t.name) as name,
            COALESCE(s.last_name, '') as last_name,
            (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_activity,
            (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN teachers t ON u.id = t.user_id
        JOIN messages m ON u.id = m.sender_id OR u.id = m.receiver_id
        WHERE u.role = ? AND (m.sender_id = ? OR m.receiver_id = ?)
        GROUP BY u.id
        ORDER BY last_activity DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiisii", $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $role, $admin_id, $admin_id);
    $stmt->execute();
    return $stmt->get_result();
}

$student_threads = getThreads($conn, $admin_id, 'student');
$teacher_threads = getThreads($conn, $admin_id, 'teacher');

// Fetch all teachers for the searchable dropdown
$all_teachers = $conn->query("SELECT u.id, t.name FROM users u JOIN teachers t ON u.id = t.user_id WHERE u.role = 'teacher' ORDER BY t.name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root { --primary: #0066FF; --dark: #0F172A; --light: #F8FAFC; --gray: #64748B; --border: #E2E8F0; }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-size: 1.8rem; color: var(--dark); font-weight: 700; margin: 0; }

        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid var(--border); overflow-x: auto; }
        .tab { padding: 1rem 1.5rem; text-decoration: none; color: var(--gray); font-weight: 600; border-bottom: 2px solid transparent; margin-bottom: -2px; white-space: nowrap; }
        .tab.active { color: var(--primary); border-bottom-color: var(--primary); }

        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; font-weight: 600; color: var(--gray); font-size: 0.85rem; text-transform: uppercase; }
        
        .unread-badge { background: #EF4444; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; margin-left: 5px; }
        .action-btn { text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
        .btn-reply { background: #E0F2FE; color: #0284C7; }
        .btn-delete { background: #FEE2E2; color: #DC2626; margin-left: 5px; border: none; cursor: pointer; }

        .compose-section { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid var(--border); }
        .btn-start { background: var(--primary); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
        
        /* Select2 Customization */
        .select2-container--default .select2-selection--single { height: 45px; border: 2px solid var(--border); border-radius: 10px; padding: 5px; }

        /* Broadcast Modal Styles */
        .broadcast-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            z-index: 10000;
            backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .b-modal-content {
            background: white; padding: 2.5rem; border-radius: 20px;
            width: 90%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .btn-broadcast { 
            background: #0066FF; 
            color: white; 
            border: none; 
            padding: 0.8rem 1.5rem; 
            border-radius: 8px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0, 102, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-broadcast:hover {
            background: #0052CC;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 102, 255, 0.3);
        }
        .btn-close { background: #E2E8F0; color: #64748B; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($success_alert)): ?>
            <div style="background: #DCFCE7; color: #166534; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 600;"><?php echo $success_alert; ?></div>
        <?php endif; ?>
        
        <div class="header">
            <h1 class="page-title">Messages</h1>
            <div style="display: flex; gap: 15px; align-items: center;">
                <button onclick="openBroadcastModal()" class="btn-broadcast">📢 Broadcast to All Teachers</button>
                <div class="compose-section" style="margin: 0;">
                    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                        <select name="teacher_user_id" id="teacherSelect" style="width: 250px;" required>
                            <option value="">Start chat with teacher...</option>
                            <?php while($t = $all_teachers->fetch_assoc()): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" name="start_teacher_chat" class="btn-start">Message Teacher</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Broadcast Modal -->
        <div id="broadcastModal" class="broadcast-modal">
            <div class="b-modal-content">
                <h2 style="margin-top: 0; color: var(--dark);">Broadcast Message</h2>
                <p style="color: var(--gray); font-size: 0.9rem; margin-bottom: 1.5rem;">This message will be sent to <strong>all teachers</strong> individually.</p>
                <form method="POST">
                    <div class="form-group">
                        <textarea name="broadcast_message" class="form-control" rows="5" placeholder="Write your announcement here..." required style="resize: vertical;"></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end;">
                        <button type="button" class="btn-close" onclick="closeBroadcastModal()">Cancel</button>
                        <button type="submit" name="broadcast_teachers" class="btn-start">Send Broadcast</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="tabs">
            <a href="?tab=inquiries" class="tab <?php echo $active_tab == 'inquiries' ? 'active' : ''; ?>">Class Inquiries</a>
            <a href="?tab=student_messages" class="tab <?php echo $active_tab == 'student_messages' ? 'active' : ''; ?>">Student Chats</a>
            <a href="?tab=teacher_messages" class="tab <?php echo $active_tab == 'teacher_messages' ? 'active' : ''; ?>">Teacher Chats</a>
        </div>

        <div class="table-container">
            <?php if ($active_tab == 'inquiries'): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class Preference</th>
                            <th>Contact</th>
                            <th>Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($inquiries_res->num_rows > 0): ?>
                            <?php while ($row = $inquiries_res->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['class_preference']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 3rem; color: var(--gray);">No inquiries found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <?php 
                $current_threads = ($active_tab == 'teacher_messages') ? $teacher_threads : $student_threads; 
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Last Message</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($current_threads->num_rows > 0): ?>
                            <?php while ($row = $current_threads->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['name'] . ' ' . $row['last_name']); ?></strong>
                                        <?php if ($row['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $row['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--gray);"><?php echo htmlspecialchars(mb_strimwidth($row['last_message'], 0, 70, "...")); ?></td>
                                    <td style="font-size: 0.85rem; color: var(--gray);"><?php echo date('M d, h:i A', strtotime($row['last_activity'])); ?></td>
                                    <td>
                                        <a href="admin_chat.php?user_id=<?php echo $row['partner_id']; ?>" class="action-btn btn-reply">Chat</a>
                                        <a href="?tab=<?php echo $active_tab; ?>&delete_thread=<?php echo $row['partner_id']; ?>&type=<?php echo $active_tab; ?>" class="action-btn btn-delete" onclick="return confirm('Delete conversation?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 3rem; color: var(--gray);">No conversations yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#teacherSelect').select2({
                placeholder: "Search for a teacher...",
                allowClear: true
            });
        });

        function openBroadcastModal() {
            document.getElementById('broadcastModal').style.display = 'flex';
        }

        function closeBroadcastModal() {
            document.getElementById('broadcastModal').style.display = 'none';
        }

        // Close modal on escape
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeBroadcastModal();
        });
    </script>
</body>
</html>
