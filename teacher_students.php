<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

// Check if teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

// Fetch Students (Teachers see all students but read-only)
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
    <title>My Students | Teacher Panel</title>
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
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        .header { margin-bottom: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box;}
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        th { color: var(--gray); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        
        .id-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.7);
            z-index: 2000;
            backdrop-filter: blur(8px);
            align-items: center; justify-content: center;
        }

        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; }
        .alert-error { background: #FEE2E2; color: #991B1B; }

    </style>
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
    <?php include 'teacher_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>My Students</h1>
            <p style="color: var(--gray);">View and lookup student details.</p>
        </div>
        
        <div class="card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                    <input type="text" id="studentSearch" class="form-control" placeholder="Search by name, username or grade..." style="padding-left: 2.8rem;">
                </div>
                <button id="qrSearchBtn" class="btn" style="background: var(--dark);">
                    <i class="fas fa-qrcode"></i> Scan ID Card
                </button>
            </div>
            <div id="qrReader" style="width: 100%; max-width: 500px; margin: 1.5rem auto 0; display: none; border-radius: 12px; overflow: hidden;"></div>
        </div>

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
                                        <button type="button" class="btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="viewStudentFullDetails(<?php echo $stu['id']; ?>)">View Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div id="studentDetailsModal" class="id-modal" onclick="closeDetailsModal(event)" style="background: rgba(15, 23, 42, 0.85);">
        <div class="card" onclick="event.stopPropagation()" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 0; position: relative;">
            <div onclick="closeDetailsModal(event)" style="position: absolute; top: 1.5rem; right: 1.5rem; cursor: pointer; font-size: 1.5rem; color: var(--gray); z-index: 10;"><i class="fas fa-times"></i></div>
            
            <div style="background: var(--primary); padding: 3rem 2rem; color: white; text-align: center;">
                <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">👤</div>
                <h2 id="detName" style="margin: 0;">Student Name</h2>
                <p id="detUsername" style="opacity: 0.8; margin: 0.5rem 0 0;">username</p>
            </div>

            <div style="padding: 2rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <h4 style="color: var(--gray); text-transform: uppercase; font-size: 0.75rem; margin-bottom: 0.8rem;">Personal Information</h4>
                        <div style="margin-bottom: 0.5rem;"><strong>Grade:</strong> <span id="detGrade">-</span></div>
                        <div style="margin-bottom: 0.5rem;"><strong>DOB:</strong> <span id="detDob">-</span></div>
                        <div style="margin-bottom: 0.5rem;"><strong>Phone:</strong> <span id="detPhone">-</span></div>
                        <div style="margin-bottom: 0.5rem;"><strong>Email:</strong> <span id="detEmail">-</span></div>
                        <div><strong>Address:</strong> <p id="detAddress" style="margin: 0.3rem 0; font-size: 0.9rem; color: var(--gray);">-</p></div>
                    </div>
                    <div>
                        <h4 style="color: var(--gray); text-transform: uppercase; font-size: 0.75rem; margin-bottom: 0.8rem;">Parental Info</h4>
                        <div style="margin-bottom: 0.5rem;"><strong>Name:</strong> <span id="detParentName">-</span></div>
                        <div style="margin-bottom: 0.5rem;"><strong>Contact:</strong> <span id="detParentPhone">-</span></div>
                        <div style="margin-bottom: 0.5rem;"><strong>Rel.:</strong> <span id="detParentRel">-</span></div>
                    </div>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 2rem;">
                    <h4 style="color: var(--gray); text-transform: uppercase; font-size: 0.75rem; margin-bottom: 1rem;">Enrolled Classes</h4>
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

    <script>
        // Search
        const studentSearch = document.getElementById('studentSearch');
        studentSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

        // QR Scanner
        const qrSearchBtn = document.getElementById('qrSearchBtn');
        const qrReader = document.getElementById('qrReader');
        let html5QrcodeScanner = null;

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

        function startQRScanner() {
            html5QrcodeScanner = new Html5QrcodeScanner("qrReader", { fps: 10, qrbox: 250 });
            html5QrcodeScanner.render((decodedText) => {
                stopQRScanner();
                viewStudentByUsername(decodedText);
            });
        }

        function stopQRScanner() {
            qrReader.style.display = 'none';
            qrSearchBtn.innerHTML = '<i class="fas fa-qrcode"></i> Scan ID Card';
            qrSearchBtn.style.background = 'var(--dark)';
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(err => console.error(err));
            }
        }

        // Student Details
        function viewStudentFullDetails(studentId) {
            fetch(`api_student_details.php?student_id=${studentId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    populateDetailsModal(data);
                })
                .catch(err => alert("Error loading details: " + err));
        }

        function viewStudentByUsername(username) {
            fetch(`api_student_details.php?username=${username}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    populateDetailsModal(data);
                })
                .catch(err => alert("Student lookup failed: " + err.message));
        }

        function populateDetailsModal(data) {
            const s = data.student;
            document.getElementById('detName').innerText = `${s.first_name} ${s.last_name}`;
            document.getElementById('detUsername').innerText = s.username;
            document.getElementById('detGrade').innerText = s.grade;
            document.getElementById('detDob').innerText = s.dob;
            document.getElementById('detPhone').innerText = s.phone;
            document.getElementById('detEmail').innerText = s.email || 'N/A';
            document.getElementById('detAddress').innerText = s.address;
            document.getElementById('detParentName').innerText = s.parent_name;
            document.getElementById('detParentPhone').innerText = s.parent_contact;
            document.getElementById('detParentRel').innerText = s.parent_relationship;
            
            const classesList = document.getElementById('detClassesList');
            classesList.innerHTML = '';
            
            if (data.classes.length === 0) {
                classesList.innerHTML = '<p style="color: var(--gray); font-style: italic;">No classes enrolled yet.</p>';
            } else {
                data.classes.forEach(c => {
                    const row = document.createElement('div');
                    row.style.background = '#f8fafc';
                    row.style.padding = '0.8rem 1.2rem';
                    row.style.borderRadius = '8px';
                    row.style.display = 'flex';
                    row.style.justifyContent = 'space-between';
                    row.style.alignItems = 'center';
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
            document.getElementById('studentDetailsModal').style.display = 'flex';
        }

        function closeDetailsModal() {
            document.getElementById('studentDetailsModal').style.display = 'none';
        }
    </script>
</body>
</html>
