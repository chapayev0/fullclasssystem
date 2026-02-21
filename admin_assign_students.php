<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Students | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --border: #E2E8F0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            margin: 0;
            display: flex;
        }

        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 290px;
        }

        .header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-container {
            position: relative;
            max-width: 500px;
            width: 100%;
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 102, 255, 0.1);
        }

        .student-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .student-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid transparent;
        }

        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .student-avatar {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .student-details h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .student-details p {
            margin: 0.2rem 0 0;
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            border-radius: 20px;
            padding: 2.5rem;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            margin-bottom: 2rem;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--dark);
        }

        .close-modal {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .enrollment-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .class-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .class-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .class-badge .remove-btn {
            cursor: pointer;
            font-weight: 800;
            color: #ef4444;
        }

        .assign-select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            margin-bottom: 1rem;
        }

        .btn-assign {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }

        .btn-assign:hover {
            background: #0052cc;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Assign Students to Classes</h1>
        </div>

        <div class="search-container">
            <input type="text" id="studentSearch" class="search-input" placeholder="Search students by name...">
        </div>

        <div id="studentList" class="student-list">
            <!-- Students populated here -->
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <div class="modal-header">
                <h2 id="modalStudentName">Student Name</h2>
                <p id="modalStudentGrade" style="color: var(--gray);"></p>
            </div>

            <div class="enrollment-section">
                <div class="section-title">Current Enrollments</div>
                <div id="currentEnrollments" class="class-badges">
                    <!-- Current classes -->
                </div>
            </div>

            <div class="enrollment-section">
                <div class="section-title">Assign to New Class</div>
                <select id="classSelect" class="assign-select">
                    <!-- Available classes -->
                </select>
                <button id="assignBtn" class="btn-assign">Assign to Class</button>
            </div>
        </div>
    </div>

    <script>
        const studentSearch = document.getElementById('studentSearch');
        const studentList = document.getElementById('studentList');
        const assignmentModal = document.getElementById('assignmentModal');
        const closeModal = document.getElementById('closeModal');
        const currentEnrollments = document.getElementById('currentEnrollments');
        const classSelect = document.getElementById('classSelect');
        const assignBtn = document.getElementById('assignBtn');
        const modalStudentName = document.getElementById('modalStudentName');
        const modalStudentGrade = document.getElementById('modalStudentGrade');

        let currentStudentId = null;

        // Fetch students on load and on search
        async function fetchStudents(query = '') {
            const res = await fetch(`api_assign_student.php?action=search_students&q=${query}`);
            const data = await res.json();
            if (data.success) {
                renderStudents(data.students);
            }
        }

        function renderStudents(students) {
            studentList.innerHTML = students.map(s => `
                <div class="student-card" onclick="openAssignmentModal(${s.id}, '${s.first_name} ${s.last_name}', '${s.formatted_grade}')">
                    <div class="student-info">
                        <div class="student-avatar">${s.first_name[0]}${s.last_name[0]}</div>
                        <div class="student-details">
                            <h3>${s.first_name} ${s.last_name}</h3>
                            <p>${s.formatted_grade}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function openAssignmentModal(id, name, grade) {
            currentStudentId = id;
            modalStudentName.innerText = name;
            modalStudentGrade.innerText = grade;
            
            await fetchEnrollments(id);
            assignmentModal.style.display = 'flex';
        }

        async function fetchEnrollments(studentId) {
            const res = await fetch(`api_assign_student.php?action=get_enrollments&student_id=${studentId}`);
            const data = await res.json();
            if (data.success) {
                // Render current enrollments
                currentEnrollments.innerHTML = data.enrollments.length ? data.enrollments.map(e => `
                    <div class="class-badge">
                        ${e.formatted_grade} - ${e.subject}
                        <span class="remove-btn" onclick="removeEnrollment(${studentId}, ${e.id})">&times;</span>
                    </div>
                `).join('') : '<p style="color: var(--gray); font-size: 0.9rem;">No enrollments yet.</p>';

                // Render class options
                const enrolledIds = data.enrollments.map(e => e.id);
                classSelect.innerHTML = '<option value="">-- Select Class --</option>' + 
                    data.all_classes.map(c => `
                        <option value="${c.id}" ${enrolledIds.includes(c.id) ? 'disabled' : ''}>
                            ${c.formatted_grade} - ${c.subject} ${enrolledIds.includes(c.id) ? '(Enrolled)' : ''}
                        </option>
                    `).join('');
            }
        }

        async function assignToClass() {
            const classId = classSelect.value;
            if (!classId) return;

            const formData = new FormData();
            formData.append('student_id', currentStudentId);
            formData.append('class_id', classId);

            const res = await fetch('api_assign_student.php?action=assign', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                fetchEnrollments(currentStudentId);
            } else {
                alert(data.message);
            }
        }

        async function removeEnrollment(studentId, classId) {
            if (!confirm('Remove student from this class?')) return;

            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('class_id', classId);

            const res = await fetch('api_assign_student.php?action=remove', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                fetchEnrollments(studentId);
            } else {
                alert(data.message);
            }
        }

        studentSearch.addEventListener('input', (e) => {
            fetchStudents(e.target.value);
        });

        closeModal.onclick = () => assignmentModal.style.display = 'none';
        window.onclick = (e) => {
            if (e.target == assignmentModal) assignmentModal.style.display = 'none';
        };

        assignBtn.onclick = assignToClass;

        // Init
        fetchStudents();
    </script>
</body>
</html>
