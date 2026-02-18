<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message_status = '';
$error = '';

// Get Admin ID (Assuming single admin for now, or pick the first one)
$admin_sql = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
$admin_res = $conn->query($admin_sql);
if ($admin_res->num_rows > 0) {
    $admin_row = $admin_res->fetch_assoc();
    $admin_id = $admin_row['id'];
} else {
    $error = "Admin not found. Cannot send messages.";
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message']) && isset($admin_id)) {
    $message_content = trim($_POST['message']);
    if (!empty($message_content)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $admin_id, $message_content);
        if ($stmt->execute()) {
            // Redirect to prevent form resubmission
            header("Location: student_messages.php");
            exit();
        } else {
            $error = "Failed to send message.";
        }
        $stmt->close();
    } else {
        $error = "Message cannot be empty.";
    }
}

// Fetch messages
$messages = [];
if (isset($admin_id)) {
    $stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
    $stmt->bind_param("iiii", $user_id, $admin_id, $admin_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --message-sent: #E0F2FE;
            --message-received: #F1F5F9;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 250px; /* Sidebar width */
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
            align-items: center;
            gap: 1rem;
            background: rgba(255,255,255,0.9);
        }

        .chat-header h2 { margin: 0; color: var(--dark); font-size: 1.25rem; }

        .messages-area {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            background: #fcfcfc;
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
            background: white;
            border: 1px solid #e2e8f0;
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

        .message-form {
            display: flex;
            gap: 1rem;
        }

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

        .message-input:focus {
            outline: none;
            border-color: var(--primary);
            height: 80px;
        }

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

        .empty-chat {
            text-align: center;
            color: var(--gray);
            margin-top: 5rem;
        }
    </style>
</head>
<body>
    <?php include 'student_sidebar.php'; ?>
    <div class="main-content">
        <div class="chat-container">
            <div class="chat-header">
                <h2>Message Teacher</h2>
            </div>
            
            <div class="messages-area" id="messagesArea">
                <?php if (empty($messages)): ?>
                    <div class="empty-chat">
                        <p>No messages yet. Start a conversation!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <span class="message-time"><?php echo date('M d, h:i A', strtotime($msg['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="input-area">
                <?php if ($error): ?>
                    <div style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="" class="message-form">
                    <textarea name="message" class="message-input" placeholder="Type your message..." required></textarea>
                    <button type="submit" name="send_message" class="btn-send">Send</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Auto-scroll to bottom
        const messagesArea = document.getElementById('messagesArea');
        messagesArea.scrollTop = messagesArea.scrollHeight;
    </script>
</body>
</html>
