<?php
// Function to check active link
function isActive($page) {
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
        <div class="logo">Admin Dashboard</div>
        <button class="close-sidebar" onclick="toggleSidebar()">×</button>
    </div>
    <nav>
        <a href="admin_dashboard.php" class="nav-link <?php echo isActive('admin_dashboard.php'); ?>">Dashboard</a>
        <a href="admin_students.php" class="nav-link <?php echo isActive('admin_students.php'); echo isActive('admin_edit_student.php'); ?>">Students</a>
        <a href="admin_classes.php" class="nav-link <?php echo isActive('admin_classes.php'); echo isActive('admin_edit_class.php'); ?>">Classes</a>
        <a href="admin_products.php" class="nav-link <?php echo isActive('admin_products.php'); echo isActive('admin_edit_product.php'); ?>">Store</a>
        <a href="admin_resources.php" class="nav-link <?php echo isActive('admin_resources.php'); echo isActive('admin_edit_resource.php'); ?>">Resources</a>
        <a href="admin_requests.php" class="nav-link <?php echo isActive('admin_requests.php'); ?>">Requests</a>
        <a href="admin_wall.php" class="nav-link <?php echo isActive('admin_wall.php'); ?>">Wall Requests</a>
        <a href="admin_playground_categories.php" class="nav-link <?php echo isActive('admin_playground_categories.php'); ?>">Playground Categories</a>
        <a href="admin_playground_games.php" class="nav-link <?php echo isActive('admin_playground_games.php'); ?>">Playground Games</a>
        <a href="admin_inbox.php" class="nav-link <?php echo isActive('admin_inbox.php'); ?>">Inbox</a>
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
    
    // Close on outside click for modal
    document.getElementById('logoutModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLogoutModal();
        }
    });
</script>

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
        color: #0066FF;
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
    
    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        z-index: 1000;
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
        transition: all 0.2s;
        text-decoration: none;
        font-size: 1rem;
    }
    
    .btn-cancel {
        background: #F1F5F9;
        color: #64748B;
    }
    
    .btn-cancel:hover {
        background: #E2E8F0;
        color: #475569;
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
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
