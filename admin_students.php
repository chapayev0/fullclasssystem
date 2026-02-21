<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';
$generated_username = '';

// Handle Delete Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    $user_id = $_POST['user_id'];
    
    // Delete from users table (cascading delete should handle students table if set up, but we'll be explicit or rely on manual deletion order)
    // Actually safe to delete user first if foreign key constraints allow, or student first.
    // Let's delete student first then user.
    
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM students WHERE id = $student_id");
        $conn->query("DELETE FROM users WHERE id = $user_id");
        $conn->commit();
        $success_msg = "Student deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Error deleting student: " . $e->getMessage();
    }
}

// Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    // Collect Data
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
        $error_msg = "Passwords do not match!";
    } else {
        // Email check
        $email_valid = true;
        if (!empty($email)) {
            $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
            if ($check->num_rows > 0) {
                $error_msg = "Email already exists!";
                $email_valid = false;
            }
        }

        if ($email_valid) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $temp_username = uniqid('temp_');
            
            $conn->begin_transaction();
            try {
                // Create User
                $email_sql_value = !empty($email) ? "'$email'" : "NULL";
                $conn->query("INSERT INTO users (username, email, password, role) VALUES ('$temp_username', $email_sql_value, '$hashed_password', 'student')");
                $user_id = $conn->insert_id;
                
                // Generate Username
                $clean_lname = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $last_name));
                $clean_fname = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name));
                $final_username = $clean_lname . $clean_fname . $user_id;

                $conn->query("UPDATE users SET username = '$final_username' WHERE id = $user_id");

                // Create Profile
                $sql_student = "INSERT INTO students (user_id, first_name, last_name, dob, phone, grade, address, gender, parent_name, parent_contact, parent_relationship) 
                              VALUES ('$user_id', '$first_name', '$last_name', '$dob', '$phone', '$grade', '$address', '$gender', '$parent_name', '$parent_contact', '$relationship')";
                
                if ($conn->query($sql_student)) {
                    $conn->commit();
                    $success_msg = "Student registered successfully!";
                    $generated_username = $final_username;
                } else {
                    throw new Exception($conn->error);
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Error adding student: " . $e->getMessage();
            }
        }
    }
}

// Fetch Students
$students = [];
$sql = "SELECT s.*, u.username, u.email, u.id as u_id FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.grade ASC, s.first_name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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
        .sidebar { width: 250px; background: var(--dark); color: white; min-height: 100vh; padding: 2rem; position: fixed; left: 0; top: 0; }
        .logo { font-size: 1.5rem; font-weight: 800; margin-bottom: 3rem; color: var(--primary); }
        .nav-link { display: block; color: rgba(255,255,255,0.7); text-decoration: none; padding: 1rem 0; transition: color 0.3s; }
        .nav-link:hover, .nav-link.active { color: white; font-weight: 600; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .header { margin-bottom: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .tab-btn { background: white; border: 1px solid #e2e8f0; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; color: var(--gray); transition: all 0.3s; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .full-width { grid-column: span 2; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box;}
        
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-danger { background: var(--danger); }
        .btn-edit { background: var(--secondary); margin-right: 0.5rem; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        th { color: var(--gray); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .hidden { display: none; }
        .username-display { font-size: 1.2rem; font-weight: 800; color: var(--primary); }
        .section-title { grid-column: span 2; color: var(--primary); font-size: 1.1rem; font-weight: 700; margin-top: 1rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }

        /* ID Card Modal Styles */
        .id-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.7);
            z-index: 2000;
            backdrop-filter: blur(8px);
            align-items: center; justify-content: center;
        }
        .id-card-container {
            background: white;
            width: 380px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            animation: idCardPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes idCardPop {
            from { transform: scale(0.8) translateY(20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        .id-card-header {
            background: linear-gradient(135deg, #0066FF, #7C3AED);
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            position: relative;
        }
        .id-card-header h2 { margin: 0; font-size: 1.2rem; letter-spacing: 2px; text-transform: uppercase; opacity: 0.9; }
        .id-card-header p { margin: 5px 0 0; font-size: 0.8rem; font-weight: 300; }
        
        .student-avatar {
            width: 100px; height: 100px;
            background: white;
            border-radius: 50%;
            margin: -50px auto 1rem;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; color: var(--primary);
            position: relative; z-index: 2;
        }

        .id-card-body { padding: 1rem 2rem 2rem; text-align: center; }
        .id-student-name { font-size: 1.4rem; font-weight: 800; color: var(--dark); margin-bottom: 0.5rem; }
        .id-detail-row { display: flex; justify-content: space-between; margin-bottom: 0.8rem; font-size: 0.9rem; border-bottom: 1px dashed #e2e8f0; padding-bottom: 0.4rem; }
        .id-label { color: var(--gray); font-weight: 600; }
        .id-value { color: var(--dark); font-weight: 700; }

        .qr-container { background: #f8fafc; padding: 1.5rem; margin-top: 1rem; border-radius: 12px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        #qrcode { padding: 10px; background: white; border-radius: 8px; }
        .qr-label { font-size: 0.75rem; font-weight: 700; color: var(--gray); text-transform: uppercase; }

        .btn-view-id { background: #F1F5F9; color: var(--primary); font-size: 0.85rem; padding: 0.5rem 1rem; margin-left: 0.5rem; }
        .btn-view-id:hover { background: #E0F2FE; }
        
        .close-id-modal {
            position: absolute;
            top: 15px; right: 15px;
            color: rgba(255,255,255,0.7);
            cursor: pointer; font-size: 1.5rem;
            z-index: 10;
        }
        .close-id-modal:hover { color: white; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Students</h1>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <?php echo $success_msg; ?>
                <?php if ($generated_username): ?>
                    <br><strong>Username:</strong> <span class="username-display"><?php echo $generated_username; ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('view')">View Students</button>
            <button class="tab-btn" onclick="switchTab('add')">Add New Student</button>
        </div>
        
        <!-- View Tab -->
        <div id="view-tab">
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name & Username</th>
                                <th>Grade</th>
                                <th>Contact</th>
                                <th>Parent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--gray);">No students registered.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $stu): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;"><?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?></div>
                                            <div style="font-size:0.9rem; color:var(--primary);"><?php echo htmlspecialchars($stu['username']); ?></div>
                                        </td>
                                        <td><?php echo format_grade($stu['grade']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($stu['phone']); ?>
                                            <?php if($stu['email']) echo '<br><small style="color:var(--gray);">' . htmlspecialchars($stu['email']) . '</small>'; ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($stu['parent_name']); ?></div>
                                            <small style="color:var(--gray);"><?php echo htmlspecialchars($stu['parent_contact']); ?></small>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items: center; gap: 5px;">
                                                <a href="admin_edit_student.php?id=<?php echo $stu['id']; ?>" class="btn btn-edit" style="margin: 0;">Edit</a>
                                                <button type="button" class="btn btn-view-id" style="margin: 0;" onclick="showIDCard('<?php echo htmlspecialchars(addslashes($stu['first_name'] . ' ' . $stu['last_name'])); ?>', '<?php echo $stu['username']; ?>', '<?php echo $stu['phone']; ?>', '<?php echo format_grade($stu['grade']); ?>')">View ID</button>
                                                <button type="button" class="btn" style="margin: 0; background: var(--secondary); padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="downloadStudentPDF('<?php echo htmlspecialchars(addslashes($stu['first_name'] . ' ' . $stu['last_name'])); ?>', '<?php echo $stu['username']; ?>', '<?php echo $stu['phone']; ?>', '<?php echo format_grade($stu['grade']); ?>')">Print ID</button>
                                                <form method="POST" onsubmit="return confirm('Deleting this student will ALSO delete their user account. Confirm?');" style="margin: 0;">
                                                    <input type="hidden" name="student_id" value="<?php echo $stu['id']; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $stu['u_id']; ?>">
                                                    <button type="submit" name="delete_student" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add Tab -->
        <div id="add-tab" class="hidden">
            <div class="card">
                <form method="POST">
                    <div class="form-grid">
                        <div class="section-title">Student Details</div>
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Grade</label>
                            <select name="grade" class="form-control" required>
                                <?php for($i=6; $i<=13; $i++) echo "<option value='$i'>" . format_grade($i) . "</option>"; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-control" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="section-title">Parent Information</div>
                        <div class="form-group">
                            <label class="form-label">Parent Name</label>
                            <input type="text" name="parent_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Parent Contact</label>
                            <input type="tel" name="parent_contact" class="form-control" required>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="relationship" class="form-control" required>
                        </div>

                        <div class="section-title">Login Credentials</div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_student" class="btn" style="margin-top: 1.5rem; width: 100%;">Register Student</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Student ID Card Modal -->
    <div id="idCardModal" class="id-modal" onclick="closeIDCard(event)">
        <div class="id-card-container" onclick="event.stopPropagation()">
            <div class="close-id-modal" onclick="closeIDCard(event)">×</div>
            <div id="printableCard">
                <div class="id-card-header">
                    <p>Digital Student ID</p>
                    <h2>ICT WITH DILHARA</h2>
                </div>
                <div class="student-avatar">👤</div>
                <div class="id-card-body">
                    <div class="id-student-name" id="idName">Student Name</div>
                    <div class="id-detail-row">
                        <span class="id-label">ID Number</span>
                        <span class="id-value" id="idNumber">ICT2024001</span>
                    </div>
                    <div class="id-detail-row">
                        <span class="id-label">Phone</span>
                        <span class="id-value" id="idPhone">077 123 4567</span>
                    </div>
                    <div class="id-detail-row">
                        <span class="id-label">Grade</span>
                        <span class="id-value" id="idGrade">Grade 10</span>
                    </div>
                    
                    <div class="qr-container">
                        <span class="qr-label">Scan to Verify</span>
                        <div id="qrcode"></div>
                    </div>
                </div>
            </div>
            <div style="padding: 0 2rem 2rem;">
                <p style="margin-top: 1rem; font-size: 0.7rem; color: var(--gray); text-align: center;">This is an electronically generated ID card, valid for active ICT with Dilhara sessions.</p>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            document.getElementById('view-tab').classList.add('hidden');
            document.getElementById('add-tab').classList.add('hidden');
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            if(event) event.target.classList.add('active');
        }

        let qrCodeInstance = null;

        function showIDCard(name, username, phone, grade) {
            document.getElementById('idName').innerText = name;
            document.getElementById('idNumber').innerText = username;
            document.getElementById('idPhone').innerText = phone;
            document.getElementById('idGrade').innerText = grade;
            
            const qrElement = document.getElementById('qrcode');
            qrElement.innerHTML = ''; // Clear previous QR
            
            // Re-initialize Select2 or QR if needed
            qrCodeInstance = new QRCode(qrElement, {
                text: username,
                width: 100,
                height: 100,
                colorDark : "#0F172A",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            document.getElementById('idCardModal').style.display = 'flex';
        }

        function closeIDCard() {
            document.getElementById('idCardModal').style.display = 'none';
        }

        function downloadStudentPDF(name, username, phone, grade) {
            // Populate the card data first (even if modal is hidden)
            document.getElementById('idName').innerText = name;
            document.getElementById('idNumber').innerText = username;
            document.getElementById('idPhone').innerText = phone;
            document.getElementById('idGrade').innerText = grade;
            
            const qrElement = document.getElementById('qrcode');
            qrElement.innerHTML = '';
            new QRCode(qrElement, {
                text: username,
                width: 100, height: 100,
                colorDark : "#0F172A",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // Wait a moment for QR to render then save
            setTimeout(() => {
                const element = document.getElementById('printableCard');
                const studentId = username;
                const studentName = name.replace(/\s+/g, '_');
                
                const opt = {
                    margin:       0,
                    filename:     `ID_${studentId}_${studentName}.pdf`,
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, letterRendering: true, backgroundColor: '#ffffff' },
                    jsPDF:        { unit: 'in', format: [4, 6], orientation: 'portrait' },
                    pagebreak:    { mode: 'avoid-all' }
                };

                html2pdf().set(opt).from(element).save();
            }, 500);
        }

        // Close on escape
        window.onkeydown = function(event) {
            if (event.keyCode == 27) closeIDCard();
        };
    </script>
</body>
</html>
