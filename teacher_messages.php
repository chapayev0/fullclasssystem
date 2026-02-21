<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// Get Admin ID
$admin_sql = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
$admin_res = $conn->query($admin_sql);
if ($admin_res->num_rows > 0) {
    $admin_row = $admin_res->fetch_assoc();
    $admin_id = $admin_row['id'];
} else {
    $error = "Admin not found.";
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message']) && isset($admin_id)) {
    $message_content = trim($_POST['message']);
    if (!empty($message_content)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $admin_id, $message_content);
        if ($stmt->execute()) {
            header("Location: teacher_messages.php");
            exit();
        } else {
            $error = "Failed to send message.";
        }
        $stmt->close();
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
    <title>Messages | Teacher Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0066FF; --dark: #0F172A; --light: #F8FAFC; --gray: #64748B; }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; height: 100vh; display: flex; flex-direction: column; }
        .chat-container { flex: 1; background: white; border-radius: 15px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; }
        .chat-header { padding: 1.5rem; border-bottom: 1px solid #e2e8f0; }
        .messages-area { flex: 1; padding: 2rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
        .message { max-width: 70%; padding: 1rem; border-radius: 15px; }
        .message.sent { align-self: flex-end; background: var(--primary); color: white; }
        .message.received { align-self: flex-start; background: #F1F5F9; color: var(--dark); }
        .input-area { padding: 1.5rem; border-top: 1px solid #e2e8f0; }
        .message-form { display: flex; gap: 1rem; }
        .message-input { flex: 1; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; }
        .btn-send { background: var(--primary); color: white; border: none; padding: 0 2rem; border-radius: 12px; cursor: pointer; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } }
    </style>
</head>
<body>
    <?php include 'teacher_sidebar.php'; ?>
    <div class="main-content">
        <div class="chat-container">
            <div class="chat-header"><h2>Admin Support</h2></div>
            <div class="messages-area" id="messagesArea">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="input-area">
                <form method="POST" action="" class="message-form">
                    <textarea name="message" class="message-input" placeholder="Type message..." required></textarea>
                    <button type="submit" name="send_message" class="btn-send">Send</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
