<?php
session_start();
include 'db_connect.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Delete Receptionist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_receptionist'])) {
    $rec_id = (int)$_POST['receptionist_id'];
    
    // Get user_id first to delete from users table
    $stmt = $conn->prepare("SELECT user_id FROM receptionists WHERE id = ?");
    $stmt->bind_param("i", $rec_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_id = $row['user_id'];
        $conn->query("DELETE FROM users WHERE id = $user_id"); // Cascades to receptionists
        $success_msg = "Receptionist deleted successfully!";
    }
}

// Handle Add Receptionist
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_receptionist'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $conn->begin_transaction();
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'reception';
        
        // Use part of email or name for username
        $temp_username = 'rec' . substr(md5(uniqid()), 0, 5);
        
        $sql_user = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("ssss", $temp_username, $email, $hashed_password, $role);
        if (!$stmt_user->execute()) throw new Exception("User creation failed: " . $stmt_user->error);
        
        $user_id = $conn->insert_id;
        
        // Update username to be more readable
        $final_username = 'rec' . $user_id;
        $conn->query("UPDATE users SET username = '$final_username' WHERE id = $user_id");

        $stmt = $conn->prepare("INSERT INTO receptionists (user_id, name, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $name, $phone, $email);
        
        if ($stmt->execute()) {
            $conn->commit();
            $success_msg = "Receptionist added successfully! Username: $final_username";
        } else {
            throw new Exception("Error adding receptionist: " . $stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = $e->getMessage();
    }
}

// Fetch Receptionists
$receptionists = [];
$result = $conn->query("SELECT r.*, u.username FROM receptionists r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $receptionists[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Receptionists | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --danger: #EF4444;
            --success: #10B981;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .header { margin-bottom: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box;}
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-danger { background: var(--danger); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        th { color: var(--gray); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Receptionists</h1>
            <p style="color: var(--gray);">Add and manage personnel with receptionist access.</p>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- Add Form -->
            <div class="card">
                <h3>Add New Receptionist</h3>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" name="add_receptionist" class="btn" style="width: 100%;">Add Receptionist</button>
                </form>
            </div>

            <!-- List -->
            <div class="card">
                <h3>Current Receptionists</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($receptionists)): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--gray);">No receptionists found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($receptionists as $r): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                                    <td><code style="background:#f1f5f9; padding:2px 5px; border-radius:4px;"><?php echo htmlspecialchars($r['username']); ?></code></td>
                                    <td>
                                        <div style="font-size:0.9rem;">📧 <?php echo htmlspecialchars($r['email']); ?></div>
                                        <div style="font-size:0.9rem;">📞 <?php echo htmlspecialchars($r['phone']); ?></div>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this receptionist?');">
                                            <input type="hidden" name="receptionist_id" value="<?php echo $r['id']; ?>">
                                            <button type="submit" name="delete_receptionist" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Delete</button>
                                        </form>
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
