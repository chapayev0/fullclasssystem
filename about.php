<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | ICT with Dilhara ICT Academy</title>
    <link rel="icon" type="image/png" href="assest/logo/logo1.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
            --primary-hover: #333333;
            --secondary: #F8F8FB;
            --accent: #E5E5E8;
            --dark: #1A1A1A;
            --light: #FFFFFF;
            --gray: #6B7280;
            --gray-light: #F3F4F6;
            --border: #E5E7EB;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            color: var(--dark);
            overflow-x: hidden;
            line-height: 1.6;
            padding-top: 80px; /* Space for fixed navbar */
        }

        /* Header Navigation - Styles provided by navbar.php */


        /* About Grid */
        .section { padding: 4rem 2rem; max-width: 1200px; margin: 0 auto; }
        .section-header { text-align: center; margin-bottom: 3rem; }
        .section-title { font-size: 2.5rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem; }
        
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
        }

        .about-image {
            width: 100%;
            height: 500px;
            background: #E5E7EB; /* Placeholder Color */
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 1.2rem;
            overflow: hidden;
            position: relative;
        }
        
        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .about-info h2 { font-size: 2rem; margin-bottom: 0.5rem; }
        .about-info h4 { color: var(--primary); font-size: 1.2rem; margin-bottom: 2rem; font-weight: 600; font-family: 'Space Mono', monospace;}
        .bio-text { color: var(--gray); font-size: 1.1rem; margin-bottom: 2rem; }
        
        .qualifications-list { list-style: none; }
        .qualifications-list li {
            margin-bottom: 1rem;
            padding-left: 2rem;
            position: relative;
            font-weight: 600;
            color: var(--dark);
        }
        .qualifications-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: 800;
        }

        /* ICT with Dilhara Section */
        .dilhara-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            padding: 60px 20px;
            background-color: var(--light);
            position: relative;
            overflow: hidden;
        }

        .dilhara-text {
            text-align: left;
            color: var(--dark);
            line-height: 1;
            position: relative;
            z-index: 2;
        }

        .dilhara-text .small-text {
            font-size: 12vw;
            font-weight: 500;
            letter-spacing: -0.02em;
            margin-bottom: -20px;
            color: var(--gray);
        }

        .dilhara-text .large-text {
            font-size: 28vw;
            font-weight: 600;
            letter-spacing: -0.03em;
            color: var(--primary);
        }

        /* Floating particles effect */
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: #60A5FA;
            border-radius: 50%;
            animation: float 3s ease-in-out infinite;
            opacity: 0.6;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
            }
            50% {
                transform: translateY(-20px) translateX(10px);
            }
        }

        .particle:nth-child(1) { top: 15%; right: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { top: 25%; right: 15%; animation-delay: 0.5s; }
        .particle:nth-child(3) { top: 35%; right: 25%; animation-delay: 1s; }
        .particle:nth-child(4) { top: 20%; right: 30%; animation-delay: 1.5s; }
        .particle:nth-child(5) { top: 30%; right: 18%; animation-delay: 2s; }
        .particle:nth-child(6) { top: 40%; left: 20%; animation-delay: 0.3s; }
        .particle:nth-child(7) { top: 25%; left: 15%; animation-delay: 0.8s; }
        .particle:nth-child(8) { top: 35%; left: 25%; animation-delay: 1.3s; }

    </style>
</head>
<body>
    <!-- Navbar -->
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- About Section -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Meet Your Instructor</h2>
            <p style="color:var(--gray);">Dedicated to empowering the next generation of tech leaders</p>
        </div>

        <div class="about-grid">
            <div class="about-image">
                <!-- Placeholder for user's photo -->
                <img src="assest/images/dilhara1.jpg" alt="Dilhara Profile">
            </div>
            <div class="about-info">
                <h2>Shashika Dilhara</h2>
                <h4>Lead Instructor & Founder</h4>
                <p class="bio-text">
                    With over 6 years of experience in ICT education, I am passionate about simplifying complex technology concepts for students. 
                    My mission is to provide high-quality, practical, and exam-focused ICT education to help students achieve excellence.
                </p>
                
                <h5 style="font-size:1.1rem; margin-bottom:1rem; color:var(--dark);">Qualifications & Achievements</h5>
                <ul class="qualifications-list">
                    <li>BICT (Hons) in Software System Technology (UOK)</li>
                    <li>Diploma in Digital Marketing (SLIM)</li>
                    <li>Web Master at Maxibot</li>
                    <li>6+ Years of Experience Software Development</li>
                    
                </ul>
            </div>
        </div>
    </section>

    <!-- ICT with Dilhara Section -->
    <?php include 'ict_section.php'; ?>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>
