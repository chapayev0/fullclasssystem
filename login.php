<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Security Check Failed: Invalid CSRF Token.");
    }

    $identifier = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Try to find user by email or username (prepared statement)
    $stmt = $conn->prepare('SELECT id, email, IFNULL(username, "") as username, password, role FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true); // Prevent Session Fixation
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['username'] = $row['username'];

            if ($row['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($row['role'] == 'teacher') {
                header("Location: teacher_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    if ($stmt) $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ICT with Dilhara ICT Academy</title>
    <link rel="icon" type="image/png" href="assest/logo/logo1.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --accent: #EC4899;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #e2e8f0 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            color: var(--dark);
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        .error-msg {
            background: #fee2e2;
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
    <script src="assest/js/password-toggle.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Please login to your account</p>
        </div>
        
        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php csrf_input(); ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>
        
        <a href="index.php" class="back-link">← Back to Home</a>
    </div>
</body>
</html>
