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

// Handle Delete Teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_teacher'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    
    // Get image path to delete file if it's local
    $stmt = $conn->prepare("SELECT image FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $image_path = $row['image'];
        if (strpos($image_path, 'uploads/teachers/') === 0 && file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    if ($conn->query("DELETE FROM teachers WHERE id = $teacher_id")) {
        $success_msg = "Teacher deleted successfully!";
    } else {
        $error_msg = "Error deleting teacher: " . $conn->error;
    }
}

// Handle Add Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_teacher'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $qualifications = mysqli_real_escape_string($conn, $_POST['qualifications']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $whatsapp = mysqli_real_escape_string($conn, $_POST['whatsapp']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $image_type = $_POST['image_type']; // 'upload' or 'url'
    $image = '';

    if ($image_type === 'url') {
        $image = mysqli_real_escape_string($conn, $_POST['image_url']);
    } else {
        if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] == 0) {
            $target_dir = "uploads/teachers/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $file_extension = pathinfo($_FILES["teacher_image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid('teacher_') . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["teacher_image"]["tmp_name"], $target_file)) {
                $image = $target_file;
            } else {
                $error_msg = "Error uploading image.";
            }
        }
    }

    // Account Data
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error_msg = "Passwords do not match!";
    }

    if (empty($error_msg)) {
        $conn->begin_transaction();
        try {
            // 1. Create User Account with temp username
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $temp_username = uniqid('t_');
            $role = 'teacher';
            
            $sql_user = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("ssss", $temp_username, $email, $hashed_password, $role);
            if (!$stmt_user->execute()) throw new Exception("User account creation failed: " . $stmt_user->error);
            
            $user_id = $conn->insert_id;
            
            // 2. Generate final username: 't' + first letter of name + last name + user_id
            $name_parts = explode(' ', trim($name));
            $fname = strtolower(preg_replace('/[^a-zA-Z]/', '', $name_parts[0]));
            $lname = (count($name_parts) > 1) ? strtolower(preg_replace('/[^a-zA-Z]/', '', end($name_parts))) : '';
            $final_username = 't' . substr($fname, 0, 1) . $lname . $user_id;
            
            $conn->query("UPDATE users SET username = '$final_username' WHERE id = $user_id");

            // 3. Create Teacher Record linked to User
            $stmt = $conn->prepare("INSERT INTO teachers (user_id, name, image, qualifications, phone, whatsapp, website, email, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $user_id, $name, $image, $qualifications, $phone, $whatsapp, $website, $email, $bio);
            
            if ($stmt->execute()) {
                $conn->commit();
                $success_msg = "Teacher added successfully!";
            } else {
                throw new Exception("Error adding teacher: " . $stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = $e->getMessage();
        }
    }
}

// Fetch Teachers
$teachers = [];
$result = $conn->query("SELECT * FROM teachers ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers | Admin</title>
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
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .tab-btn { background: white; border: 1px solid #e2e8f0; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; color: var(--gray); transition: all 0.3s; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--dark); font-weight: 600; }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box;}
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-danger { background: var(--danger); }
        .btn-edit { background: var(--secondary); margin-right: 0.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        th { color: var(--gray); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .hidden { display: none; }
        .teacher-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; background: #e2e8f0; }

        /* ID Card Modal Styles (Shared with Students) */
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
        
        .teacher-avatar-circle {
            width: 100px; height: 100px;
            background: white;
            border-radius: 50%;
            margin: -50px auto 1rem;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            position: relative; z-index: 2;
        }
        .teacher-avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .teacher-avatar-circle .placeholder { font-size: 3rem; color: var(--primary); }

        .id-card-body { padding: 1rem 2rem 2rem; text-align: center; }
        .id-teacher-name { font-size: 1.4rem; font-weight: 800; color: var(--dark); margin-bottom: 0.5rem; }
        .id-detail-row { display: flex; justify-content: space-between; margin-bottom: 0.8rem; font-size: 0.9rem; border-bottom: 1px dashed #e2e8f0; padding-bottom: 0.4rem; }
        .id-label { color: var(--gray); font-weight: 600; }
        .id-value { color: var(--dark); font-weight: 700; }

        .qr-container { background: #f8fafc; padding: 1.5rem; margin-top: 1rem; border-radius: 12px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        #qrcode { padding: 10px; background: white; border-radius: 8px; }
        .qr-label { font-size: 0.75rem; font-weight: 700; color: var(--gray); text-transform: uppercase; }

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
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Teachers</h1>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('view')">View Teachers</button>
            <button class="tab-btn" onclick="switchTab('add')">Add Teacher</button>
        </div>
        
        <!-- View Tab -->
        <div id="view-tab">
            <div class="card" style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                        <input type="text" id="teacherSearch" class="form-control" placeholder="Search by name, qualifications or email..." style="padding-left: 2.8rem;">
                    </div>
                    <button id="qrSearchBtn" class="btn" style="background: var(--dark);">
                        <i class="fas fa-qrcode"></i> Scan ID to View
                    </button>
                </div>
                <div id="qrReader" style="width: 100%; max-width: 500px; margin: 1.5rem auto 0; display: none; border-radius: 12px; overflow: hidden;"></div>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Qualifications</th>
                            <th>Contact Info</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--gray);">No teachers found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $t): ?>
                                <tr>
                                    <td><img src="<?php echo htmlspecialchars($t['image']); ?>" class="teacher-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($t['name']); ?>&background=random'"></td>
                                    <td><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t['qualifications']); ?></td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: var(--gray);">
                                            <?php if($t['phone']): ?><div>📞 <?php echo htmlspecialchars($t['phone']); ?></div><?php endif; ?>
                                            <?php if($t['whatsapp']): ?><div>💬 <?php echo htmlspecialchars($t['whatsapp']); ?></div><?php endif; ?>
                                            <?php if($t['email']): ?><div>📧 <?php echo htmlspecialchars($t['email']); ?></div><?php endif; ?>
                                            <?php if($t['website']): ?><div>🌐 <a href="<?php echo htmlspecialchars($t['website']); ?>" target="_blank" style="color: var(--primary);">Website</a></div><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                                            <button type="button" class="btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="viewTeacherFullDetails(<?php echo $t['id']; ?>)">View</button>
                                            <a href="admin_edit_teacher.php?id=<?php echo $t['id']; ?>" class="btn btn-edit" style="margin: 0; padding: 0.5rem 1rem; font-size: 0.85rem;">Edit</a>
                                            <button type="button" class="btn" style="background: var(--dark); padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="showTeacherID('<?php echo htmlspecialchars(addslashes($t['name'])); ?>', '<?php echo $t['id']; ?>', '<?php echo htmlspecialchars($t['image']); ?>', '<?php echo htmlspecialchars($t['phone']); ?>', '<?php echo htmlspecialchars(addslashes($t['qualifications'])); ?>')">ID</button>
                                            <button type="button" class="btn" style="background: var(--secondary); padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="downloadTeacherPDF('<?php echo htmlspecialchars(addslashes($t['name'])); ?>', '<?php echo $t['id']; ?>', '<?php echo htmlspecialchars($t['image']); ?>', '<?php echo htmlspecialchars($t['phone']); ?>', '<?php echo htmlspecialchars(addslashes($t['qualifications'])); ?>')">Print</button>
                                            <form method="POST" onsubmit="return confirm('Confirm deletion?');" style="margin: 0;">
                                                <input type="hidden" name="teacher_id" value="<?php echo $t['id']; ?>">
                                                <button type="submit" name="delete_teacher" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Delete</button>
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
        
        <!-- Add Tab -->
        <div id="add-tab" class="hidden">
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qualifications</label>
                        <input type="text" name="qualifications" class="form-control" placeholder="e.g. BSc in IT (Hons), 5+ Years Exp" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 0777 695 130">
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" name="whatsapp" class="form-control" placeholder="e.g. 0777 695 130">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. teacher@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Website URL</label>
                            <input type="url" name="website" class="form-control" placeholder="e.g. https://example.com">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bio (Briefly)</label>
                        <textarea name="bio" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image Input Mode</label>
                        <select name="image_type" class="form-control" onchange="toggleImageInput(this.value)">
                            <option value="upload">Upload Image</option>
                            <option value="url">Image URL</option>
                        </select>
                    </div>
                    <div class="form-group" id="upload-group">
                        <label class="form-label">Upload Image</label>
                        <input type="file" name="teacher_image" class="form-control" accept="image/*">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group hidden" id="url-group">
                        <label class="form-label">Image URL</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                    </div>
                    <button type="submit" name="add_teacher" class="btn" style="width: 100%; margin-top: 1rem;">Add Teacher</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Teacher Details Full Modal -->
    <div id="teacherDetailsModal" class="id-modal" onclick="closeDetailsModal(event)" style="background: rgba(15, 23, 42, 0.85);">
        <div class="card" onclick="event.stopPropagation()" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 0; position: relative;">
            <div onclick="closeDetailsModal(event)" style="position: absolute; top: 1.5rem; right: 1.5rem; cursor: pointer; font-size: 1.5rem; color: var(--gray); z-index: 10;"><i class="fas fa-times"></i></div>
            
            <div style="background: var(--primary); padding: 3rem 2rem; color: white; text-align: center;">
                <div id="detImgContainer" style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <span style="font-size: 3rem;">👤</span>
                </div>
                <h2 id="detName" style="margin: 0;">Teacher Name</h2>
                <p id="detUsername" style="opacity: 0.8; margin: 0.5rem 0 0;">username</p>
            </div>

            <div style="padding: 2rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <h4 style="color: var(--gray); text-transform: uppercase; font-size: 0.75rem; margin-bottom: 0.8rem;">Professional Info</h4>
                        <div style="margin-bottom: 0.5rem;"><strong>Qualifications:</strong> <p id="detQuals" style="margin: 0.3rem 0; font-size: 0.9rem;">-</p></div>
                        <div style="margin-bottom: 0.5rem;"><strong>Bio:</strong> <p id="detBio" style="margin: 0.3rem 0; font-size: 0.85rem; color: var(--gray); font-style: italic;">-</p></div>
                    </div>
                    <div>
                        <h4 style="color: var(--gray); text-transform: uppercase; font-size: 0.75rem; margin-bottom: 0.8rem;">Contact Details</h4>
                        <div style="margin-bottom: 0.5rem;"><strong>Phone:</strong> <span id="detPhone">-</span></div>
                        <div style="margin-bottom: 0.5rem;"><strong>WhatsApp:</strong> <span id="detWa">-</span></div>
                        <div style="margin-bottom: 0.5rem;"><strong>Email:</strong> <span id="detEmail">-</span></div>
                        <div style="margin-bottom: 0.5rem;"><strong>Website:</strong> <a id="detWeb" href="#" target="_blank" style="color: var(--primary); font-size: 0.9rem;">Visit Website</a></div>
                    </div>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 2rem;">
                    <h4 style="color: var(--gray); text-transform: uppercase; font-size: 0.75rem; margin-bottom: 1rem;">Assigned Classes</h4>
                    <div id="detClassesList" style="display: grid; gap: 0.8rem;">
                        <!-- Classes populated here -->
                    </div>
                </div>
            </div>
            
            <div style="padding: 1.5rem; background: #f8fafc; text-align: right;">
                <button class="btn" onclick="closeDetailsModal(event)">Close</button>
            </div>
        </div>
    </div>

    <!-- Teacher ID Card Modal -->
    <div id="idCardModal" class="id-modal" onclick="closeIDCard(event)">
        <div class="id-card-container" onclick="event.stopPropagation()">
            <div class="close-id-modal" onclick="closeIDCard(event)">×</div>
            <div id="printableCard">
                <div class="id-card-header">
                    <p>Faculty Identification</p>
                    <h2>ICT WITH DILHARA</h2>
                </div>
                <div class="teacher-avatar-circle" id="idAvatar">
                    <span class="placeholder">👤</span>
                </div>
                <div class="id-card-body">
                    <div class="id-teacher-name" id="idName">Teacher Name</div>
                    <div class="id-detail-row">
                        <span class="id-label">Staff No.</span>
                        <span class="id-value" id="idNumber">TCH-001</span>
                    </div>
                    <div class="id-detail-row">
                        <span class="id-label">Phone</span>
                        <span class="id-value" id="idPhone">077 123 4567</span>
                    </div>
                    <div class="id-detail-row">
                        <span class="id-label">Specialization</span>
                        <span class="id-value" id="idQuals">IT Specialist</span>
                    </div>
                    
                    <div class="qr-container">
                        <span class="qr-label">Scan to Verify</span>
                        <div id="qrcode"></div>
                    </div>
                </div>
            </div>
            <div style="padding: 0 2rem 2rem;">
                <p style="margin-top: 1rem; font-size: 0.7rem; color: var(--gray); text-align: center;">Authorized Faculty Member of ICT with Dilhara Educational Institute.</p>
            </div>
        </div>
    </div>
    
    <script>
        // 1. Search Functionality
        const teacherSearch = document.getElementById('teacherSearch');
        if (teacherSearch) {
            teacherSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // 2. Tab Switching
        function switchTab(tabName) {
            document.getElementById('view-tab').classList.add('hidden');
            document.getElementById('add-tab').classList.add('hidden');
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            if(window.event && window.event.target) {
                 window.event.target.classList.add('active');
            }
        }

        // 3. QR Search
        const qrSearchBtn = document.getElementById('qrSearchBtn');
        const qrReader = document.getElementById('qrReader');
        let html5QrcodeScanner = null;

        if (qrSearchBtn) {
            qrSearchBtn.addEventListener('click', function() {
                if (qrReader.style.display === 'none' || qrReader.style.display === '') {
                    qrReader.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-times"></i> Stop Scanning';
                    this.style.background = 'var(--danger)';
                    startQRScanner();
                } else {
                    stopQRScanner();
                }
            });
        }

        function startQRScanner() {
            html5QrcodeScanner = new Html5QrcodeScanner("qrReader", { fps: 10, qrbox: 250 });
            html5QrcodeScanner.render((decodedText) => {
                stopQRScanner();
                viewTeacherByUsername(decodedText);
            });
        }

        function stopQRScanner() {
            qrReader.style.display = 'none';
            qrSearchBtn.innerHTML = '<i class="fas fa-qrcode"></i> Scan ID to View';
            qrSearchBtn.style.background = 'var(--dark)';
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(error => { console.error("Failed to clear scanner", error); });
            }
        }

        // 4. Detailed View
        function viewTeacherFullDetails(teacherId) {
            fetch(`api_teacher_details.php?teacher_id=${teacherId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    populateDetailsModal(data);
                })
                .catch(err => alert("Error loading details: " + err));
        }

        function viewTeacherByUsername(username) {
            fetch(`api_teacher_details.php?username=${username}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    populateDetailsModal(data);
                })
                .catch(err => alert("Teacher lookup failed: " + err.message));
        }

        function populateDetailsModal(data) {
            const t = data.teacher;
            document.getElementById('detName').innerText = t.name;
            document.getElementById('detUsername').innerText = t.username;
            document.getElementById('detQuals').innerText = t.qualifications;
            document.getElementById('detBio').innerText = t.bio || 'No bio available.';
            document.getElementById('detPhone').innerText = t.phone || 'N/A';
            document.getElementById('detWa').innerText = t.whatsapp || 'N/A';
            document.getElementById('detEmail').innerText = t.email || t.user_email || 'N/A';
            
            const webLink = document.getElementById('detWeb');
            if(t.website) {
                webLink.href = t.website;
                webLink.style.display = 'inline';
            } else {
                webLink.style.display = 'none';
            }

            const imgCont = document.getElementById('detImgContainer');
            if(t.image) {
                imgCont.innerHTML = `<img src="${t.image}" style="width:100%; height:100%; object-fit:cover;">`;
            } else {
                imgCont.innerHTML = `<span style="font-size: 3rem;">👤</span>`;
            }
            
            const classesList = document.getElementById('detClassesList');
            classesList.innerHTML = '';
            
            if (data.classes.length === 0) {
                classesList.innerHTML = '<p style="color: var(--gray); font-style: italic;">No classes assigned yet.</p>';
            } else {
                data.classes.forEach(c => {
                    const row = document.createElement('div');
                    row.style.cssText = "background: #f8fafc; padding: 0.8rem 1.2rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;";
                    row.innerHTML = `
                        <div>
                            <div style="font-weight: 700; color: var(--dark);">${c.subject}</div>
                            <div style="font-size: 0.8rem; color: var(--gray);">Grade ${c.grade}</div>
                        </div>
                        <div style="text-align: right; font-size: 0.85rem; color: var(--primary); font-weight: 600;">
                            ${c.schedule_day}<br>${c.schedule_time}
                        </div>
                    `;
                    classesList.appendChild(row);
                });
            }
            
            document.getElementById('teacherDetailsModal').style.display = 'flex';
        }

        function closeDetailsModal() {
            document.getElementById('teacherDetailsModal').style.display = 'none';
        }

        // 5. ID Card Functionality
        let qrCodeInstance = null;

        function showTeacherID(name, id, image, phone, quals) {
            fetch(`api_teacher_details.php?teacher_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    const t = data.teacher;
                    document.getElementById('idName').innerText = t.name;
                    document.getElementById('idNumber').innerText = 'TCH-' + t.username.toUpperCase();
                    document.getElementById('idPhone').innerText = t.phone || 'N/A';
                    document.getElementById('idQuals').innerText = t.qualifications;

                    const avatar = document.getElementById('idAvatar');
                    if(t.image) {
                        avatar.innerHTML = `<img src="${t.image}">`;
                    } else {
                        avatar.innerHTML = `<span class="placeholder">👤</span>`;
                    }

                    const qrElement = document.getElementById('qrcode');
                    qrElement.innerHTML = '';
                    qrCodeInstance = new QRCode(qrElement, {
                        text: t.username,
                        width: 100,
                        height: 100,
                        colorDark : "#0F172A",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });

                    document.getElementById('idCardModal').style.display = 'flex';
                });
        }

        function closeIDCard() {
            document.getElementById('idCardModal').style.display = 'none';
        }

        function downloadTeacherPDF(name, id, image, phone, quals) {
            fetch(`api_teacher_details.php?teacher_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    const t = data.teacher;
                    document.getElementById('idName').innerText = t.name;
                    document.getElementById('idNumber').innerText = 'TCH-' + t.username.toUpperCase();
                    document.getElementById('idPhone').innerText = t.phone || 'N/A';
                    document.getElementById('idQuals').innerText = t.qualifications;

                    const avatar = document.getElementById('idAvatar');
                    if(t.image) {
                        avatar.innerHTML = `<img src="${t.image}">`;
                    } else {
                        avatar.innerHTML = `<span class="placeholder">👤</span>`;
                    }

                    const qrElement = document.getElementById('qrcode');
                    qrElement.innerHTML = '';
                    new QRCode(qrElement, {
                        text: t.username,
                        width: 100, height: 100,
                        colorDark : "#0F172A",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });

                    setTimeout(() => {
                        const element = document.getElementById('printableCard');
                        const opt = {
                            margin:       0,
                            filename:     `ID_Teacher_${t.username}.pdf`,
                            image:        { type: 'jpeg', quality: 0.98 },
                            html2canvas:  { scale: 2, useCORS: true, letterRendering: true, backgroundColor: '#ffffff' },
                            jsPDF:        { unit: 'in', format: [4, 6], orientation: 'portrait' }
                        };
                        html2pdf().set(opt).from(element).save();
                    }, 500);
                });
        }

        function toggleImageInput(value) {
            if (value === 'upload') {
                document.getElementById('upload-group').classList.remove('hidden');
                document.getElementById('url-group').classList.add('hidden');
            } else {
                document.getElementById('upload-group').classList.add('hidden');
                document.getElementById('url-group').classList.remove('hidden');
            }
        }

        window.onkeydown = function(event) {
            if (event.keyCode == 27) {
                closeIDCard();
                closeDetailsModal();
            }
        };
    </script>
</body>
</html>
