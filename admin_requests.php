<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Rejection
if (isset($_POST['reject_request'])) {
    $req_id = $_POST['request_id'];
    if ($conn->query("DELETE FROM registration_requests WHERE id = $req_id")) {
        $success_msg = "Request rejected and removed.";
    } else {
        $error_msg = "Error rejecting request: " . $conn->error;
    }
}

// Handle Approval
if (isset($_POST['approve_request'])) {
    $req_id = $_POST['request_id'];
    
    // Fetch request details
    $req_query = $conn->query("SELECT * FROM registration_requests WHERE id = $req_id");
    if ($req_query->num_rows > 0) {
        $req = $req_query->fetch_assoc();
        
        $email = $req['email'];
        // Check if email already exists in users (double check)
        $email_val = !empty($email) ? "'$email'" : "NULL";
        $email_check_cond = !empty($email) ? "email = '$email'" : "1=0";
        
        $check = $conn->query("SELECT id FROM users WHERE $email_check_cond");
        if ($check->num_rows > 0) {
            $error_msg = "User with this email already exists! Cannot approve.";
        } else {
            $conn->begin_transaction();
            try {
                // 1. Create User
                // Use temp username first
                $temp_username = uniqid('temp_');
                $password = $req['password']; // Already hashed
                
                $sql_user = "INSERT INTO users (username, email, password, role) VALUES ('$temp_username', $email_val, '$password', 'student')";
                if (!$conn->query($sql_user)) throw new Exception("User Insert Error: " . $conn->error);
                
                $user_id = $conn->insert_id;
                
                // 2. Generate Final Username
                $clean_lname = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $req['last_name']));
                $clean_fname = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $req['first_name']));
                $final_username = $clean_lname . $clean_fname . $user_id;
                
                $conn->query("UPDATE users SET username = '$final_username' WHERE id = $user_id");
                
                // 3. Create Student Profile
                $stmt = $conn->prepare("INSERT INTO students (user_id, first_name, last_name, dob, phone, grade, address, gender, parent_name, parent_contact, parent_relationship) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssssss", $user_id, $req['first_name'], $req['last_name'], $req['dob'], $req['phone'], $req['grade'], $req['address'], $req['gender'], $req['parent_name'], $req['parent_contact'], $req['relationship']);
                
                if (!$stmt->execute()) throw new Exception("Student Insert Error: " . $stmt->error);
                
                // 4. Delete Request
                $conn->query("DELETE FROM registration_requests WHERE id = $req_id");
                
                $conn->commit();
                $success_msg = "Request approved! New student user <strong>$final_username</strong> created.";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Approval failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch Pending Requests
$requests = [];
$result = $conn->query("SELECT * FROM registration_requests WHERE status = 'pending' ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Requests | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0066FF; --secondary: #7C3AED; --dark: #0F172A; --light: #F8FAFC; --gray: #64748B; --danger: #EF4444; --success: #10B981; }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        /* Sidebar Styles - Required as admin_sidebar.php doesn't include them */
        .sidebar { width: 250px; background: var(--dark); color: white; min-height: 100vh; padding: 2rem; position: fixed; left: 0; top: 0; }
        .logo { font-size: 1.5rem; font-weight: 800; margin-bottom: 3rem; color: var(--primary); }
        .nav-link { display: block; color: rgba(255,255,255,0.7); text-decoration: none; padding: 1rem 0; transition: color 0.3s; }
        .nav-link:hover, .nav-link.active { color: white; font-weight: 600; }

        /* Fixed margin to 290px to match other pages */
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        th { color: var(--gray); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; color: white; margin-right: 0.5rem; text-decoration: none; display: inline-block;}
        .btn-view { background: #3B82F6; }
        .btn-approve { background: var(--success); }
        .btn-reject { background: var(--danger); }
        .badge { background: #e0f2fe; color: #0284c7; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.8rem; }

        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 2rem;
            border: 1px solid #888;
            border-radius: 15px;
            width: 90%; 
            max-width: 600px;
            position: relative;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        .detail-row { display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; }
        .detail-label { font-weight: 600; color: var(--gray); }
        .detail-value { color: var(--dark); font-weight: 500; }
        .modal-actions { margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <h1 style="color:var(--dark);">Registration Requests</h1>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <?php if (empty($requests)): ?>
                <p style="text-align:center; color:var(--gray); padding: 2rem;">No new registration requests.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student Name</th>
                            <th>Details</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <div style="font-weight:700; color:var(--dark);"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></div>
                                    <div style="font-size:0.9rem; color:var(--gray);"><?php echo format_grade($req['grade']); ?></div>
                                </td>
                                <td>
                                    <div><strong>Parent:</strong> <?php echo htmlspecialchars($req['parent_name']); ?></div>
                                    <div style="font-size:0.9rem; color:var(--gray);"><?php echo htmlspecialchars($req['relationship']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($req['phone']); ?></div>
                                    <?php if($req['email']): ?>
                                        <div style="font-size:0.85rem; color:var(--primary);"><?php echo htmlspecialchars($req['email']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Store data in attributes for modal -->
                                    <button class="btn btn-view" 
                                        onclick='openModal(<?php echo htmlspecialchars(json_encode($req), ENT_QUOTES, "UTF-8"); ?>)'>
                                        View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="margin-top:0; color:var(--primary);">Request Details</h2>
            
            <div id="modalDetails">
                <!-- Javascript will populate this -->
            </div>

            <div class="modal-actions">
                <form method="POST" style="display:flex; gap:1rem;">
                    <input type="hidden" name="request_id" id="modalRequestId">
                    <button type="submit" name="reject_request" class="btn btn-reject" onclick="return confirm('Reject this request?');">Reject</button>
                    <button type="submit" name="approve_request" class="btn btn-approve">Approve</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById("requestModal");
        const modalDetails = document.getElementById("modalDetails");
        const modalRequestId = document.getElementById("modalRequestId");

        function openModal(data) {
            let html = '';
            const fields = {
                'Full Name': data.first_name + ' ' + data.last_name,
                'Grade': data.grade,
                'Date of Birth': data.dob,
                'Gender': data.gender,
                'Address': data.address,
                'Phone': data.phone,
                'Email': data.email || 'N/A',
                'Parent Name': data.parent_name,
                'Parent Contact': data.parent_contact,
                'Relationship': data.relationship,
                'Status': data.status,
                'Request Date': data.created_at
            };

            for (const [key, value] of Object.entries(fields)) {
                html += `
                    <div class="detail-row">
                        <div class="detail-label">${key}</div>
                        <div class="detail-value">${value}</div>
                    </div>
                `;
            }

            modalDetails.innerHTML = html;
            modalRequestId.value = data.id;
            modal.style.display = "block";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
