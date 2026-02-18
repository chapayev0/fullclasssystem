<?php
include 'db_connect.php';

$message = '';
$error = '';

// Initialize variables
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
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists in users or requests
        $email_check = false;
        if (!empty($email)) {
            $check1 = $conn->query("SELECT id FROM users WHERE email = '$email'");
            $check2 = $conn->query("SELECT id FROM registration_requests WHERE email = '$email' AND status = 'pending'");
            if ($check1->num_rows > 0 || $check2->num_rows > 0) {
                $error = "Email already registered or request pending!";
                $email_check = true;
            }
        }

        if (!$email_check) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $email_val = !empty($email) ? "'$email'" : "NULL";
            
            $sql = "INSERT INTO registration_requests (first_name, last_name, dob, phone, grade, address, gender, parent_name, parent_contact, relationship, email, password, status) 
                    VALUES ('$first_name', '$last_name', '$dob', '$phone', '$grade', '$address', '$gender', '$parent_name', '$parent_contact', '$relationship', $email_val, '$hashed_password', 'pending')";
            
            if ($conn->query($sql) === TRUE) {
                $message = "Registration request sent successfully! Please wait for admin approval.";
            } else {
                $error = "Error sending request: " . $conn->error;
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
    <title>Student Registration | ICT with Dilhara</title>
    <link rel="icon" type="image/png" href="assest/logo/logo1.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .card { background: white; padding: 2.5rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 800px; }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo img { height: 60px; }
        h2 { text-align: center; color: var(--dark); margin-bottom: 2rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .full-width { grid-column: span 2; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; }
        input, select, textarea { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .section-title { grid-column: span 2; color: var(--primary); font-size: 1.1rem; font-weight: 700; margin-top: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .btn { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 1rem; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; margin-top: 2rem; font-size: 1.1rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #ef4444; }
        .login-link { text-align: center; margin-top: 1.5rem; color: var(--gray); }
        .login-link a { color: var(--primary); text-decoration: none; font-weight: 600; }
        /* Responsive */
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } .section-title { grid-column: span 1; } }
    </style>
    <script src="assest/js/password-toggle.js" defer></script>
</head>
<body>
    <div class="card">
        <div class="logo">
            <img src="assest/logo/logo1.png" alt="ICT with Dilhara Logo">
        </div>
        <h2>Student Registration</h2>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
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
                    <label>Grade</label>
                    <select name="grade" required>
                        <option value="">Select Grade</option>
                        <?php for($i=6; $i<=11; $i++) {
                            $selected = ($grade == $i) ? 'selected' : '';
                            echo "<option value='$i' $selected>Grade $i</option>"; 
                        } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male" <?php if($gender == 'Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if($gender == 'Female') echo 'selected'; ?>>Female</option>
                        <option value="Other" <?php if($gender == 'Other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Address</label>
                    <textarea name="address" rows="2" required><?php echo htmlspecialchars($address); ?></textarea>
                </div>
                <div class="form-group full-width">
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
                    <input type="text" name="relationship" placeholder="e.g. Father, Mother" value="<?php echo htmlspecialchars($relationship); ?>" required>
                </div>

                <!-- Login Credentials -->
                <div class="section-title">Login Credentials</div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
            </div>
            
            <button type="submit" class="btn">Submit Registration Request</button>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a><br>
                <a href="index.php">Back to Home</a>
            </div>
        </form>
    </div>
</body>
</html>
