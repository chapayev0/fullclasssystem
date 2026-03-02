<?php
// Function to check active link
function isRecActive($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page === $page) ? 'active' : '';
}
?>
<div class="mobile-toggle" onclick="toggleSidebar()">
    <span></span>
    <span></span>
    <span></span>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">Reception</div>
        <button class="close-sidebar" onclick="toggleSidebar()">×</button>
    </div>
    <nav>
        <a href="reception_dashboard.php" class="nav-link <?php echo isRecActive('reception_dashboard.php'); ?>">Dashboard</a>
        <a href="admin_attendance.php" class="nav-link <?php echo isRecActive('admin_attendance.php'); ?>">Attendance & Fees</a>
        <a href="admin_students.php" class="nav-link <?php echo isRecActive('admin_students.php'); echo isRecActive('admin_edit_student.php'); echo isRecActive('add_student.php'); ?>">My Students</a>
        <a href="admin_teachers.php" class="nav-link <?php echo isRecActive('admin_teachers.php'); ?>">Teachers</a>
        <a href="admin_classes.php" class="nav-link <?php echo isRecActive('admin_classes.php'); ?>">Classes</a>
        <a href="admin_messages.php" class="nav-link <?php echo isRecActive('admin_messages.php'); ?>">Messages</a>
        <a href="#" class="nav-link" onclick="openLogoutModal()">Logout</a>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal-overlay">
    <div class="modal-content">
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to log out?</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
            <a href="logout.php" class="btn-confirm">Logout</a>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    function openLogoutModal() {
        document.getElementById('logoutModal').style.display = 'flex';
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }
</script>

<style>
    /* Global Reset for Sidebar Context */
    * { box-sizing: border-box; }

    .sidebar { width: 250px; background: #0F172A; color: white; min-height: 100vh; padding: 2rem; position: fixed; left: 0; top: 0; z-index: 1000; transition: transform 0.3s ease; }
    .nav-link { display: block; color: rgba(255,255,255,0.7); text-decoration: none; padding: 1rem 0; transition: color 0.3s; }
    .nav-link:hover, .nav-link.active { color: white; font-weight: 600; }
    .sidebar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .logo { font-size: 1.5rem; font-weight: 800; color: #10B981; }
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1000; backdrop-filter: blur(4px); align-items: center; justify-content: center; }
    .modal-content { background: white; padding: 2rem; border-radius: 12px; width: 90%; max-width: 400px; text-align: center; }
    .modal-actions { display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; }
    .btn-cancel, .btn-confirm { padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; }
    .btn-confirm { background: #EF4444; color: white; text-decoration: none; }
    @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } }
</style>
