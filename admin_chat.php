<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$error = '';

// Verify student exists and get details
$student_sql = "
    SELECT u.email, s.first_name, s.last_name 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    WHERE u.id = $student_id AND u.role = 'student'
";
$student_res = $conn->query($student_sql);
if ($student_res->num_rows == 0) {
    echo "Student not found."; // Simple error handling
    exit();
}
$student = $student_res->fetch_assoc();

// Mark messages as read
$conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $student_id AND receiver_id = $admin_id AND is_read = 0");

// Handle sending reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message_content = trim($_POST['message']);
    if (!empty($message_content)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $admin_id, $student_id, $message_content);
        if ($stmt->execute()) {
            header("Location: admin_chat.php?student_id=" . $student_id);
            exit();
        } else {
            $error = "Failed to send message.";
        }
        $stmt->close();
    }
}

// Fetch conversation
$messages = [];
$stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$stmt->bind_param("iiii", $admin_id, $student_id, $student_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($student['first_name']); ?> - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 250px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; }
        }

        .chat-container {
            flex: 1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .chat-header h2 { margin: 0; color: var(--dark); font-size: 1.1rem; }
        .back-link { text-decoration: none; color: var(--gray); font-size: 0.9rem; display: flex; align-items: center; gap: 5px; }
        .back-link:hover { color: var(--primary); }

        .messages-area {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            background: #fff;
        }

        .message {
            max-width: 70%;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            position: relative;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        .message.sent {
            align-self: flex-end;
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.received {
            align-self: flex-start;
            background: #F1F5F9;
            color: var(--dark);
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.4rem;
            display: block;
            text-align: right;
        }

        .input-area {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #e2e8f0;
        }

        .message-form { display: flex; gap: 1rem; }
        .message-input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            resize: none;
            height: 50px;
            transition: all 0.3s;
        }
        .message-input:focus { outline: none; border-color: var(--primary); height: 80px; }
        .btn-send {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-send:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="chat-container">
            <div class="chat-header">
                <div>
                    <a href="admin_inbox.php?tab=messages" class="back-link">← Back to Inbox</a>
                </div>
                <h2>Chat with <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></h2>
            </div>
            
            <div class="messages-area" id="messagesArea">
                <?php if (empty($messages)): ?>
                    <p style="text-align: center; color: var(--gray); margin-top: 2rem;">No messages yet.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender_id'] == $admin_id ? 'sent' : 'received'; ?>">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <span class="message-time"><?php echo date('M d, h:i A', strtotime($msg['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="input-area">
                <form method="POST" action="" class="message-form">
                    <textarea name="message" class="message-input" placeholder="Type your reply..." required></textarea>
                    <button type="submit" name="send_message" class="btn-send">Reply</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        const messagesArea = document.getElementById('messagesArea');
        messagesArea.scrollTop = messagesArea.scrollHeight;
    </script>
</body>
</html>
