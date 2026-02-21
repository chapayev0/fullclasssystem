<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<style>
    /* Navbar Styles */
    .navbar {
        position: fixed;
        top: 0;
        width: 100%;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        z-index: 1000;
        border-bottom: 1px solid #E5E7EB;
    }

    .navbar-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.2rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-size: 1.8rem;
        font-weight: 800;
        color: #000000;
        font-family: 'Space Mono', monospace;
    }

    .nav-menu {
        display: flex;
        align-items: center;
        flex: 1; /* Take up remaining space */
    }

    .nav-links {
        display: flex;
        gap: 2.5rem;
        list-style: none;
        align-items: center;
        margin: 0 auto; /* Center in the nav-menu space */
    }

    .nav-links a {
        text-decoration: none;
        color: #1A1A1A;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        position: relative;
    }

    .nav-links a.active {
        color: #000000;
    }

    .nav-links a::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 0;
        height: 2px;
        background: #000000;
        transition: width 0.3s ease;
    }

    .nav-links a:hover::after, .nav-links a.active::after {
        width: 100%;
    }
    
    .nav-buttons {
        display: flex;
        gap: 1rem;
        margin-left: 0; /* Align right naturally in flex flow */
    }

    .btn {
        padding: 0.7rem 1.8rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
        text-decoration: none;
        display: inline-block;
    }

    .btn-outline {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        color: #1A1A1A;
    }

    .btn-outline:hover {
        background: #F8F8FB;
        border-color: #E5E5E8;
    }

    .btn-primary {
        background: #000000;
        color: #FFFFFF;
    }

    .btn-primary:hover {
        background: #333333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Mobile Menu Button */
    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        flex-direction: column;
        gap: 6px;
        padding: 5px;
        z-index: 1100;
    }
    
    .mobile-menu-btn span {
        width: 30px;
        height: 3px;
        background: #1A1A1A;
        transition: all 0.3s ease;
        border-radius: 3px;
    }

    .mobile-menu-btn.active span:nth-child(2) { opacity: 0; }
    .mobile-menu-btn.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -6px); }

    /* Dropdown Styles */
    .dropdown {
        position: relative;
        height: 100%;
        display: flex;
        align-items: center;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        top: 100%; /* Position right below the navbar */
        left: 0;
        background-color: #ffffff;
        min-width: 220px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 0.5rem 0;
        z-index: 1000;
        border: 1px solid #f0f0f0;
        margin-top: 1.2rem; /* Matches padding of navbar-container to align */
    }

    .dropdown-content::before {
        content: '';
        position: absolute;
        top: -2rem;
        left: 0;
        width: 100%;
        height: 2rem;
        background: transparent;
    }

    .dropdown:hover .dropdown-content {
        display: block;
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .dropdown-content a {
        padding: 12px 20px;
        display: block;
        color: #333;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .dropdown-content a:hover {
        background-color: #f8f9fa;
        color: #000;
        padding-left: 25px; /* Slide effect */
    }

    .dropdown-content a::after {
        display: none; /* Remove underline from main nav styles */
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .nav-links {
            gap: 1.5rem;
        }
        .navbar-container {
             padding: 1.2rem 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .mobile-menu-btn {
            display: flex;
        }

        .nav-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 85%;
            height: 100vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: -10px 0 30px rgba(0,0,0,0.1);
            padding: 2rem;
            z-index: 1000;
            margin-left: 0;
        }

        .nav-menu.active {
            right: 0;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            margin-bottom: 2rem;
            margin-top: 0;
            text-align: center;
            font-size: 1.2rem;
        }

        .dropdown {
            flex-direction: column;
            width: 100%;
        }

        .dropdown-content {
            position: static;
            display: none;
            width: 100%;
            box-shadow: none;
            border: none;
            background: #f8f9fa;
            margin-top: 0;
            padding: 0;
        }

        .dropdown:hover .dropdown-content {
            display: block;
            animation: none;
        }

        .dropdown-content a {
            padding: 15px;
            font-size: 1rem;
            text-align: center;
        }
        
        .nav-buttons {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 1rem;
        }

        .nav-buttons .btn {
            width: 100%;
            text-align: center;
            padding: 1rem;
        }
    }
</style>

<nav class="navbar">
    <div class="navbar-container">
        <div class="logo">
            <img src="assest/logo/logo1.png" alt="ICT with Dilhara Logo" style="height: 50px;">
        </div>
        
        <button class="mobile-menu-btn" onclick="toggleNav()">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="index.php#home">Home</a></li>
                
                <li class="dropdown">
                    <a href="#" style="cursor: default">Courses ▾</a>
                    <div class="dropdown-content">
                        <a href="reg/index.html">Robotics Course</a>
                    </div>
                </li>
                
                <li><a href="index.php#classes">Classes</a></li>
                <li><a href="about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">About</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary" style="text-decoration:none;">Register</a>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleNav() {
        document.getElementById('navMenu').classList.toggle('active');
        document.querySelector('.mobile-menu-btn').classList.toggle('active');
    }
</script>
