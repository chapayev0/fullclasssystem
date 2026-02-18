<div class="mobile-toggle" onclick="toggleSidebar()">
    <span></span>
    <span></span>
    <span></span>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">Student Dashboard</div>
        <button class="close-sidebar" onclick="toggleSidebar()">×</button>
    </div>
    <nav>
        <a href="student_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
        <a href="link_library.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'link_library.php' ? 'active' : ''; ?>">Link Library</a>
        <a href="student_profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_profile.php' ? 'active' : ''; ?>">My Profile</a>
        <a href="student_messages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_messages.php' ? 'active' : ''; ?>">Messages</a>
        <a href="wall_of_talent.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wall_of_talent.php' ? 'active' : ''; ?>">Wall of Talent</a>
        <a href="#" onclick="openLogoutModal()" class="nav-link">Logout</a>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

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

<!-- Shared Logout Modal -->
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

<style>
    /* Global Reset for Sidebar Context */
    * {
        box-sizing: border-box;
    }
    
    /* Mobile Toggle */
    .mobile-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: #0066FF;
        color: white;
        padding: 10px;
        border-radius: 8px;
        cursor: pointer;
        flex-direction: column;
        gap: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .mobile-toggle span {
        width: 25px;
        height: 3px;
        background: white;
        border-radius: 2px;
    }

    /* Sidebar Header & Close Button */
    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .logo {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0066FF; /* Ensure consistent color */
    }
    
    .close-sidebar {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }

    /* Sidebar Base Styles */
    .sidebar {
        width: 250px;
        background: #0F172A;
        color: white;
        min-height: 100vh;
        padding: 2rem;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        transition: transform 0.3s ease;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 999;
        backdrop-filter: blur(2px);
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

    /* Responsive Media Query */
    @media (max-width: 768px) {
        .mobile-toggle {
            display: flex;
        }

        .sidebar {
            transform: translateX(-100%);
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-overlay.active {
            display: block;
        }

        .close-sidebar {
            display: block;
        }
        
        .sidebar-header .logo {
            margin-bottom: 0;
        }
        
        /* Force main content adjustment globally */
        body .main-content {
            margin-left: 0 !important;
            padding: 1.5rem !important;
            padding-top: 5rem !important; /* Space for toggle */
        }
    }

    /* Logout Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        z-index: 9999;
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease-out;
    }
    
    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        width: 90%;
        max-width: 400px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        transform: translateY(20px);
        animation: slideUp 0.3s ease-out forwards;
    }
    
    .modal-content h3 {
        margin-top: 0;
        color: #0F172A;
        font-size: 1.5rem;
    }
    
    .modal-content p {
        color: #64748B;
        margin-bottom: 2rem;
    }
    
    .modal-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }
    
    .btn-cancel, .btn-confirm {
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        text-decoration: none;
        font-family: inherit;
        font-size: 1rem;
    }

    .btn-cancel {
        background: #e2e8f0;
        color: #64748B;
    }

    .btn-confirm {
        background: #EF4444;
        color: white;
    }
    
    .btn-confirm:hover {
        background: #DC2626;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>
