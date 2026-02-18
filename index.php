<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT with Dilhara ICT Academy | Excellence in Digital Education</title>
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
        }

        /* Promo Banner Styles */
        .promo-banner {
            background: linear-gradient(135deg, #0062E6 0%, #33AEFF 100%);
            padding: 5rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            box-sizing: border-box;
        }

        .promo-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        .promo-title {
            font-family: 'Space Mono', monospace;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            line-height: 1.2;
        }

        .promo-desc {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .promo-btn {
            background: white;
            border: none;
            color: #0062E6;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .promo-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .banner-robot {
            position: absolute;
            right: 5%;
            bottom: -20px;
            width: 400px;
            height: auto;
            transform: rotate(-10deg);
            filter: drop-shadow(-20px 20px 30px rgba(0,0,0,0.5));
            z-index: 1;
            opacity: 0.9;
            pointer-events: none;
        }

        .promo-bg-decoration {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 20%),
                              radial-gradient(circle at 90% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 20%);
            z-index: 0;
        }

        /* Specific override for when .section class is added to promo-banner */
        .promo-banner.section {
            max-width: none !important;
            margin-left: calc(-50vw + 50%) !important;
            margin-right: calc(-50vw + 50%) !important;
            padding: 5rem 2rem !important;
            width: 100vw !important;
        }

        /* Responsive Styles */
        @media (max-width: 968px) {
            .promo-banner {
                padding: 4rem 1.5rem;
                text-align: left;
                justify-content: flex-start;
            }

            .promo-content {
                margin: 0;
                max-width: 60%;
            }
            
            .promo-desc {
                margin-left: 0;
            }

            .banner-robot {
                width: 300px;
                right: -50px;
                opacity: 0.8;
            }
        }

        @media (max-width: 768px) {
            .promo-banner {
                padding: 3rem 1.5rem;
                flex-direction: column;
                text-align: center;
                justify-content: center;
            }

            .promo-content {
                max-width: 100%;
                z-index: 3;
            }

            .promo-title {
                font-size: 1.8rem;
                margin-bottom: 0.8rem;
            }

            .promo-desc {
                font-size: 1rem;
                margin-bottom: 2rem;
            }

            .promo-btn {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }

            .banner-robot {
                position: relative;
                width: 250px;
                right: auto;
                bottom: auto;
                margin-top: 2rem;
                transform: rotate(-5deg);
                opacity: 1;
                filter: drop-shadow(-10px 10px 20px rgba(0,0,0,0.4));
            }
        }

        /* Header Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid var(--border);
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
            color: var(--primary);
            font-family: 'Space Mono', monospace;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
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
            background: var(--light);
            border: 1px solid var(--border);
            color: var(--dark);
        }

        .btn-outline:hover {
            background: var(--secondary);
            border-color: var(--accent);
        }

        .btn-primary {
            background: var(--primary);
            color: var(--light);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Hero Section with Slider */
        .hero-section {
            margin-top: 80px;
            height: 600px;
            position: relative;
            overflow: hidden;
            background: var(--light);
        }

        .hero-slider {
            position: relative;
            height: 100%;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-1 {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 248, 251, 0.98)),
                        url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect fill="%23F8F8FB" width="100" height="100"/><circle cx="50" cy="50" r="30" fill="%23E5E5E8" opacity="0.5"/></svg>');
        }

        .slide-2 {
            background: linear-gradient(135deg, rgba(248, 248, 251, 0.98), rgba(255, 255, 255, 0.98)),
                        url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect fill="%23FFFFFF" width="100" height="100"/><polygon points="50,20 90,80 10,80" fill="%23F8F8FB" opacity="0.5"/></svg>');
        }

        .slide-3 {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(243, 244, 246, 0.98)),
                        url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect fill="%23F3F4F6" width="100" height="100"/><rect x="20" y="20" width="60" height="60" fill="%23E5E7EB" opacity="0.5"/></svg>');
        }

        .slide-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: var(--dark);
            max-width: 900px;
            padding: 2rem;
        }

        .slide-title {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            animation: slideUp 0.8s ease;
            color: var(--primary);
        }

        .slide-description {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            font-weight: 400;
            animation: slideUp 0.8s ease 0.2s backwards;
            color: var(--gray);
        }

        .slide-btn {
            animation: slideUp 0.8s ease 0.4s backwards;
        }

        @keyframes slideUp {
            from {
                transform: translateY(40px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .slider-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: var(--primary);
            width: 30px;
            border-radius: 6px;
        }

        /* Section Container */
        .section {
            padding: 6rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }


        /* Classes Section */
        .classes-carousel {
            position: relative;
            overflow: hidden;
            padding: 2rem 0;
        }

        .classes-wrapper {
            display: flex;
            gap: 2rem;
            transition: transform 0.5s ease;
        }


        .class-card {
            background: var(--light);
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            border: 1px solid var(--border);
        }

        .class-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .class-card:hover::before {
            transform: scaleX(1);
        }

        .class-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent);
        }

        .class-icon {
            width: 80px;
            height: 80px;
            background: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .class-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .class-description {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .class-btn {
            background: var(--primary);
            color: var(--light);
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .class-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        /* Institutes Section */
        .institutes-section {
            background: var(--secondary);
            color: var(--dark);
            padding: 6rem 0;
            margin: 0;
            width: 100%;
        }

        .institutes-section .section-header {
            padding: 0 2rem;
        }

        .logo-slider {
            overflow: hidden;
            position: relative;
            padding: 2rem 0;
        }

        .logo-track {
            display: flex;
            gap: 4rem;
            animation: scroll 30s linear infinite;
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }

        .institute-card {
            min-width: 320px;
            background: var(--light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 1rem;
            gap: 1rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            text-align: left;
            transition: transform 0.3s ease;
        }

        .institute-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .institute-logo-box {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            border: 1px solid var(--border);
        }

        .institute-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .institute-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 0.2rem;
            line-height: 1.2;
        }

        .institute-location {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
        }



        /* Store Section */
        .store-carousel {
            position: relative;
            overflow: hidden;
            padding: 2rem 0;
        }

        .products-wrapper {
            display: flex;
            gap: 2rem;
            transition: transform 0.5s ease;
        }

        .product-card {
            min-width: 350px;
            background: var(--light);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .product-image {
            width: 100%;
            height: 250px;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: var(--dark);
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 2rem;
        }

        .product-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .product-description {
            color: var(--gray);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .product-price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .product-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-cart {
            flex: 1;
            background: var(--primary);
            color: var(--light);
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cart:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-cart.secondary {
            background: var(--secondary);
            color: var(--dark);
            border: 1px solid var(--border);
        }

        .btn-cart.secondary:hover {
            background: var(--accent);
        }

        .carousel-nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .carousel-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--light);
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-btn:hover {
            background: var(--primary-hover);
            transform: scale(1.1);
        }

        /* Testimonials Section */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .testimonial-card {
            background: var(--light);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .quote-icon {
            font-size: 3rem;
            color: var(--accent);
            opacity: 0.5;
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .testimonial-text {
            color: var(--gray);
            margin-bottom: 2rem;
            font-style: italic;
            line-height: 1.8;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-weight: 700;
            font-size: 1.5rem;
            border: 2px solid var(--border);
        }

        .author-info h4 {
            font-weight: 700;
            color: var(--dark);
        }

        .author-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .stars {
            color: var(--primary);
            margin-top: 0.5rem;
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

        /* Footer */
        .footer {
            background: var(--secondary);
            color: var(--dark);
            padding: 4rem 2rem 2rem;
            border-top: 1px solid var(--border);
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-brand h3 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            font-family: 'Space Mono', monospace;
            color: var(--primary);
        }

        .footer-brand p {
            color: var(--gray);
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .social-link:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-3px);
        }

        .footer-section h4 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
            padding-left: 5px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
            color: var(--gray);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .nav-links {
                gap: 1.5rem;
            }

            .classes-grid,
            .testimonials-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .contact-tiles {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .classes-grid,
            .testimonials-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .contact-tiles {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            /* Content Stacking */
            .slide-title {
                font-size: 2.5rem;
            }

            .slide-description {
                font-size: 1rem;
            }

            .classes-grid,
            .testimonials-grid,
            .contact-tiles {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .navbar-container {
                padding: 1rem;
            }

            .product-card {
                min-width: 280px;
            }

            .dilhara-text .small-text {
                font-size: 14vw;
            }

            .dilhara-text .large-text {
                font-size: 30vw;
            }
            
            .hero-section {
                height: auto;
                min-height: 90vh;
            }
            
            .slide-content {
                position: relative;
                top: auto;
                left: auto;
                transform: none;
                padding-top: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <!-- Navigation Header -->
    <?php include 'navbar.php'; ?>

    <!-- Hero Section with Slider -->
    <section class="hero-section" id="home">
        <div class="hero-slider">
            <div class="slide slide-1 active">
                <div class="slide-content">
                    <h1 class="slide-title">Master ICT Skills for the Digital Future</h1>
                    <p class="slide-description">Join Sri Lanka's premier ICT academy and unlock your potential with expert guidance and comprehensive curriculum</p>
                    <button class="btn btn-primary slide-btn">Explore Classes</button>
                </div>
            </div>
            <div class="slide slide-2">
                <div class="slide-content">
                    <h1 class="slide-title">Learn from Industry Experts</h1>
                    <p class="slide-description">Our experienced instructors bring real-world knowledge to help you excel in O/L ICT examinations</p>
                    <button class="btn btn-primary slide-btn">Meet Our Team</button>
                </div>
            </div>
            <div class="slide slide-3">
                <div class="slide-content">
                    <h1 class="slide-title">Flexible Online & Physical Classes</h1>
                    <p class="slide-description">Choose your learning path with our hybrid model - attend in person or join from anywhere in Sri Lanka</p>
                    <button class="btn btn-primary slide-btn" onclick="openJoinModal()">Join Online</button>
                </div>
            </div>
        </div>
        <div class="slider-dots">
            <span class="dot active" onclick="currentSlide(0)"></span>
            <span class="dot" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
        </div>
    </section>

    <!-- Classes Section -->
    <section class="section" id="classes">
        <div class="section-header">
            <h2 class="section-title">Our Classes</h2>
            <p class="section-subtitle">Diverse range of subjects to empower your knowledge, from Sciences to Arts.</p>
        </div>
        
        <style>
             .class-card {
                min-width: 370px; /* Consistent width for carousel */
                padding: 0; /* Remove padding to let image flush */
                display: flex;
                flex-direction: column;
            }

            .class-image {
                width: 100%;
                height: 200px;
                background: var(--secondary);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 4rem;
                color: var(--primary);
            }

            .class-content {
                padding: 2rem;
                flex: 1;
                display: flex;
                flex-direction: column;
            }

            .class-title {
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }

            .class-description {
                flex: 1;
                font-size: 0.95rem;
            }
        </style>

        <div class="classes-carousel">
            <div class="classes-wrapper" id="classesWrapper">
                <!-- Science -->
                <div class="class-card">
                    <div class="class-image">🔬</div>
                    <div class="class-content">
                        <h3 class="class-title">Science</h3>
                        <p class="class-description">Explore the wonders of biology, chemistry, and physics with hands-on experiments.</p>
                        <a href="#" class="class-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Time Table</a>
                    </div>
                </div>

                <!-- Mathematics -->
                <div class="class-card">
                    <div class="class-image">📐</div>
                    <div class="class-content">
                        <h3 class="class-title">Mathematics</h3>
                        <p class="class-description">Master algebra, geometry, and calculus with our expert guidance.</p>
                        <a href="#" class="class-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Time Table</a>
                    </div>
                </div>

                <!-- Arts -->
                <div class="class-card">
                    <div class="class-image">🎨</div>
                    <div class="class-content">
                        <h3 class="class-title">Arts</h3>
                        <p class="class-description">Unleash your creativity through drawing, painting, and digital art.</p>
                        <a href="#" class="class-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Time Table</a>
                    </div>
                </div>

                <!-- Economics -->
                <div class="class-card">
                    <div class="class-image">📊</div>
                    <div class="class-content">
                        <h3 class="class-title">Economics</h3>
                        <p class="class-description">Understand market dynamics, finance, and global economic trends.</p>
                        <a href="#" class="class-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Time Table</a>
                    </div>
                </div>

                <!-- History -->
                <div class="class-card">
                    <div class="class-image">🏛️</div>
                    <div class="class-content">
                        <h3 class="class-title">History</h3>
                        <p class="class-description">Journey through time to understand the events that shaped our world.</p>
                        <a href="#" class="class-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Time Table</a>
                    </div>
                </div>

                <!-- ICT (Kept as one general item) -->
                <div class="class-card">
                    <div class="class-image">💻</div>
                    <div class="class-content">
                        <h3 class="class-title">ICT</h3>
                        <p class="class-description">Comprehensive computer science education from basics to advanced programming.</p>
                        <a href="#" class="class-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Time Table</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="carousel-nav">
            <button class="carousel-btn" onclick="scrollClasses(-1)">‹</button>
            <button class="carousel-btn" onclick="scrollClasses(1)">›</button>
        </div>
    </section>

    <!-- Robotics Promo Banner -->
    <section class="promo-banner section">
        <div class="promo-content">
            <h2 class="promo-title">Robotics Mastery Course</h2>
            <p class="promo-desc">Unlock the future with hands-on learning in Arduino, electronics, and coding. Designed for beginners and enthusiasts alike.</p>
            <a href="reg/index.html" class="promo-btn">
                Enroll Now <span style="margin-left: 8px;">→</span>
            </a>
        </div>
        
        <!-- Robot Car Image -->
        <img src="assest/images/robot_car_banner.png" alt="Robot Car" class="banner-robot">

        <!-- Tech Background Decoration -->
        <div class="promo-bg-decoration"></div>
    </section>

    <!-- Teachers Section -->
    <section class="section" id="teachers">
        <div class="section-header">
            <h2 class="section-title">Our Best Teachers</h2>
            <p class="section-subtitle">Learn from qualified and experienced educators dedicated to your success.</p>
        </div>

        <style>
            .teachers-carousel {
                position: relative;
                overflow: hidden;
                padding: 2rem 0;
            }

            .teachers-wrapper {
                display: flex;
                gap: 2rem;
                transition: transform 0.5s ease;
            }

            .teacher-card {
                min-width: 300px;
                background: var(--light);
                border-radius: 12px;
                overflow: hidden;
                box-shadow: var(--shadow);
                border: 1px solid var(--border);
                text-align: center;
                padding-bottom: 2rem;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .teacher-image {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                margin: 2rem auto 1rem;
                object-fit: cover;
                border: 4px solid var(--secondary);
            }

            .teacher-info {
                padding: 0 1.5rem;
            }

            .teacher-name {
                font-size: 1.4rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
                color: var(--primary);
            }

            .teacher-qual {
                font-size: 0.9rem;
                color: var(--gray);
                margin-bottom: 0.5rem;
                font-style: italic;
            }

            .teacher-class {
                background: var(--secondary);
                padding: 0.3rem 0.8rem;
                border-radius: 20px;
                font-size: 0.85rem;
                font-weight: 600;
                display: inline-block;
                margin-bottom: 1rem;
                color: var(--dark);
            }

            .teacher-contact {
                color: var(--primary);
                font-weight: 600;
                text-decoration: none;
                font-size: 0.95rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }

            .teacher-contact:hover {
                text-decoration: underline;
            }
        </style>

        <div class="teachers-carousel">
            <div class="teachers-wrapper" id="teachersWrapper">
                <!-- Teacher 1 -->
                <div class="teacher-card">
                    <img src="assest/images/dilhara1.jpg" alt="Mr. S. Dilhara" class="teacher-image" onerror="this.src='https://ui-avatars.com/api/?name=S+Dilhara&background=random'">
                    <div class="teacher-info">
                        <h3 class="teacher-name">Mr. S. Dilhara</h3>
                        <p class="teacher-qual">BSc in IT (Hons), 5+ Years Exp</p>
                        <span class="teacher-class">ICT (Grade 6-11)</span>
                        <a href="tel:0777695130" class="teacher-contact">📞 0777 695 130</a>
                    </div>
                </div>

                <!-- Teacher 2 -->
                <div class="teacher-card">
                    <img src="https://ui-avatars.com/api/?name=Sarah+Perera&background=random" alt="Ms. Sarah Perera" class="teacher-image">
                    <div class="teacher-info">
                        <h3 class="teacher-name">Ms. Sarah Perera</h3>
                        <p class="teacher-qual">BSc in Mathematics, PGDE</p>
                        <span class="teacher-class">Mathematics</span>
                        <a href="tel:0712345678" class="teacher-contact">📞 071 234 5678</a>
                    </div>
                </div>

                <!-- Teacher 3 -->
                <div class="teacher-card">
                    <img src="https://ui-avatars.com/api/?name=Kamal+Dias&background=random" alt="Mr. Kamal Dias" class="teacher-image">
                    <div class="teacher-info">
                        <h3 class="teacher-name">Mr. Kamal Dias</h3>
                        <p class="teacher-qual">BSc in Science, MSc</p>
                        <span class="teacher-class">Science</span>
                        <a href="tel:0771122334" class="teacher-contact">📞 077 112 2334</a>
                    </div>
                </div>

                <!-- Teacher 4 -->
                <div class="teacher-card">
                    <img src="https://ui-avatars.com/api/?name=Nimali+Silva&background=random" alt="Ms. Nimali Silva" class="teacher-image">
                    <div class="teacher-info">
                        <h3 class="teacher-name">Ms. Nimali Silva</h3>
                        <p class="teacher-qual">BA in English (Hons)</p>
                        <span class="teacher-class">English</span>
                        <a href="tel:0755566778" class="teacher-contact">📞 075 556 6778</a>
                    </div>
                </div>
                
                <!-- Teacher 5 -->
                 <div class="teacher-card">
                    <img src="https://ui-avatars.com/api/?name=Ruwan+Fernando&background=random" alt="Mr. Ruwan Fernando" class="teacher-image">
                    <div class="teacher-info">
                        <h3 class="teacher-name">Mr. Ruwan Fernando</h3>
                        <p class="teacher-qual">BA in History, MPhil</p>
                        <span class="teacher-class">History</span>
                        <a href="tel:0789900112" class="teacher-contact">📞 078 990 0112</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-nav">
            <button class="carousel-btn" onclick="scrollTeachers(-1)">‹</button>
            <button class="carousel-btn" onclick="scrollTeachers(1)">›</button>
        </div>
    </section>



    <!-- Store Section -->
    <section class="section" id="store">
        <div class="section-header">
            <h2 class="section-title">ICT Learning Resources</h2>
            <p class="section-subtitle">Premium study materials, textbooks, and resources to support your ICT learning journey</p>
        </div>
        <div class="store-carousel">
            <div class="products-wrapper" id="productsWrapper">
                <?php
                // Fetch products with their primary image
                $products_sql = "SELECT p.*, 
                                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                                (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) as fallback_image
                                FROM store_products p 
                                ORDER BY p.created_at ASC";
                $products_res = $conn->query($products_sql);
                
                if ($products_res->num_rows > 0) {
                    while ($product = $products_res->fetch_assoc()) {
                        // Use primary image, otherwise fallback (e.g. first added), otherwise placeholder
                        $display_image = $product['primary_image'] ?: $product['fallback_image'] ?: '📦';
                ?>
                <div class="product-card">
                    <a href="product_details.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                        <div class="product-image">
                            <?php if (filter_var($display_image, FILTER_VALIDATE_URL) || file_exists($display_image) || strpos($display_image, 'assest/') === 0): ?>
                                <img src="<?php echo htmlspecialchars($display_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <?php echo htmlspecialchars($display_image); ?>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="product-price">Rs. <?php echo number_format($product['price'], 0); ?></div>
                        </div>
                    </a>
                    <div class="product-info" style="padding-top: 0;">
                        <div class="product-actions">
                            <button class="btn-cart">Buy Now</button>
                            <button class="btn-cart secondary">Add to Cart</button>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo '<p style="text-align:center; padding: 2rem;">No products available at the moment.</p>';
                }
                ?>
            </div>
        </div>
        <div class="carousel-nav">
            <button class="carousel-btn" onclick="scrollProducts(-1)">‹</button>
            <button class="carousel-btn" onclick="scrollProducts(1)">›</button>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section" id="testimonials">
        <div class="section-header">
            <h2 class="section-title">What Our Students Say</h2>
            <p class="section-subtitle">Read success stories from students who achieved excellence with our guidance</p>
        </div>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="quote-icon">"</div>
                <p class="testimonial-text">ICT with Dilhara Academy transformed my understanding of ICT. The teachers are amazing and the materials are comprehensive. I scored an A in my O/Levels!</p>
                <div class="testimonial-author">
                    <div class="author-avatar">AS</div>
                    <div class="author-info">
                        <h4>Amara Silva</h4>
                        <p>Grade 10 Student</p>
                        <div class="stars">★★★★★</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote-icon">"</div>
                <p class="testimonial-text">The online classes are incredibly convenient and interactive. I can learn at my own pace and the support from teachers is excellent. Highly recommend!</p>
                <div class="testimonial-author">
                    <div class="author-avatar">KP</div>
                    <div class="author-info">
                        <h4>Kavindi Perera</h4>
                        <p>Grade 9 Student</p>
                        <div class="stars">★★★★★</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote-icon">"</div>
                <p class="testimonial-text">Best ICT class in Colombo! The practical approach and exam-focused teaching helped me build confidence. My son improved from C to A in just 6 months.</p>
                <div class="testimonial-author">
                    <div class="author-avatar">NF</div>
                    <div class="author-info">
                        <h4>Nimal Fernando</h4>
                        <p>Parent</p>
                        <div class="stars">★★★★★</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ICT with Dilhara Section -->
    <?php include 'ict_section.php'; ?>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Join Modal -->
    <?php include 'modal_join.php'; ?>

    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script src="assest/js/main.js"></script>
</body>
</html>