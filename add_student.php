<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$generated_username = '';

// Initialize variables to keep form data
$first_name = '';
$last_name = '';
$dob = '';
$phone = '';
$grade = '';
$address = '';
$gender = '';
$parent_name = '';
$parent_contact = '';
$relationship = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect Student Data first to use for username generation
    $first_name_raw = $_POST['first_name'];
    $last_name_raw = $_POST['last_name'];
    
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $dob = $_POST['dob'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $gender = $_POST['gender'];
    $parent_name = mysqli_real_escape_string($conn, $_POST['parent_name']);
    $parent_contact = mysqli_real_escape_string($conn, $_POST['parent_contact']);
    $relationship = mysqli_real_escape_string($conn, $_POST['relationship']);
    $email = mysqli_real_escape_string($conn, $_POST['email']); // Optional email

    // Password Validation
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Create User with temp username
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Use a temp unique username initially to satisfy constraint if needed, or just insert.
        // Since we need ID to generate username, we first insert with a placeholder or just random string
        $temp_username = uniqid('temp_');
        
        // We still check email uniqueness if provided and we want it unique. 
        // For now, let's assume email is optional or just for record, skipping unique check unless strictly required.
        // If email field is unique in DB, we need to handle it. The previous setup made email unique.
        // Let's check if email is provided and unique.
        
        $email_valid = true;
        if (!empty($email)) {
            $check_email = "SELECT id FROM users WHERE email = '$email'";
            if ($conn->query($check_email)->num_rows > 0) {
                $error = "Email already exists!";
                $email_valid = false;
            }
        }

        if ($email_valid) {
            // Prepare email value for SQL insertion
            $email_sql_value = !empty($email) ? "'$email'" : "NULL";
            
            $sql_user = "INSERT INTO users (username, email, password, role) VALUES ('$temp_username', $email_sql_value, '$hashed_password', 'student')";
            
            if ($conn->query($sql_user) === TRUE) {
                $user_id = $conn->insert_id;
                
                // Generate Username: lname + fname + id
                // Sanitize names for username (remove spaces, special chars)
                $clean_lname = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $last_name));
                $clean_fname = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name));
                $final_username = $clean_lname . $clean_fname . $user_id;

                // Update User with final username
                $sql_update = "UPDATE users SET username = '$final_username' WHERE id = $user_id";
                $conn->query($sql_update);

                // Create Student Profile
                $sql_student = "INSERT INTO students (user_id, first_name, last_name, dob, phone, grade, address, gender, parent_name, parent_contact, parent_relationship) 
                              VALUES ('$user_id', '$first_name', '$last_name', '$dob', '$phone', '$grade', '$address', '$gender', '$parent_name', '$parent_contact', '$relationship')";
                
                if ($conn->query($sql_student) === TRUE) {
                    $message = "Student added successfully!";
                    $generated_username = $final_username;
                } else {
                    $error = "Error adding student details: " . $conn->error;
                    $conn->query("DELETE FROM users WHERE id = $user_id");
                }
            } else {
                $error = "Error creating user account: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            margin: 0;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background: var(--dark);
            color: white;
            min-height: 100vh;
            padding: 2rem;
            position: fixed;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 3rem;
            color: var(--primary);
        }
        .nav-link {
            display: block;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 1rem 0;
            transition: color 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            font-weight: 600;
        }
        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 290px;
        }
        .card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        h2 {
            margin-top: 0;
            color: var(--dark);
            margin-bottom: 2rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .full-width {
            grid-column: span 2;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.9rem;
        }
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            box-sizing: border-box;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        .section-title {
            grid-column: span 2;
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 700;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 2rem;
            width: 100%;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
        }
        .alert-error {
            background: #fee2e2;
            color: #ef4444;
        }
        .bg-blue-100 {
            background-color: #e0f2fe;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #bae6fd;
        }
        .username-display {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            display: block;
            margin-top: 0.5rem;
        }
    </style>
    <script src="assest/js/password-toggle.js" defer></script>
</head>
<body>
    <div class="sidebar">
        <div class="logo">Admin Dashboard</div>
        <nav>
            <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
            <a href="add_student.php" class="nav-link active">Add Student</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <div class="card">
            <h2>Register New Student</h2>
            
            <?php if($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                    <?php if($generated_username): ?>
                        <div style="margin-top: 1rem; border-top: 1px solid #bbf7d0; padding-top: 1rem;">
                            <strong>Student Credentials Generated:</strong><br>
                            Username: <span class="username-display"><?php echo $generated_username; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <!-- Student Details -->
                    <div class="section-title">Student Details</div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($dob); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                    </div>
                    <div class="form-group">
                        <select name="grade" required>
                            <option value="">Select Grade</option>
                            <?php for($i=6; $i<=13; $i++) echo "<option value='$i' " . ($grade == $i ? 'selected' : '') . ">" . format_grade($i) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php if($gender == 'Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if($gender == 'Female') echo 'selected'; ?>>Female</option>
                            <option value="Other" <?php if($gender == 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email (Optional)</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    </div>

                    <!-- Parent Information -->
                    <div class="section-title">Parent/Guardian Information</div>
                    <div class="form-group">
                        <label>Parent Name</label>
                        <input type="text" name="parent_name" value="<?php echo htmlspecialchars($parent_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Parent Contact</label>
                        <input type="tel" name="parent_contact" value="<?php echo htmlspecialchars($parent_contact); ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Relationship to Child</label>
                        <input type="text" name="relationship" placeholder="e.g. Father, Mother, Guardian" value="<?php echo htmlspecialchars($relationship); ?>" required>
                    </div>

                    <!-- Login Credentials -->
                    <div class="section-title">Login Credentials</div>
                    <div class="form-group full-width">
                        <p style="color: var(--gray); font-size: 0.9rem;">
                            Username will be auto-generated upon registration.
                        </p>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Retype Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Register Student</button>
            </form>
        </div>
    </div>
</body>
</html>
