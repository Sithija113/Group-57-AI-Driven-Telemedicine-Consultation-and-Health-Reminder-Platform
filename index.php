<?php
// index.php - Midnight Purple Theme
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediConnect - Telemedicine Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0f;
            color: white;
            overflow-x: hidden;
        }

        /* Animated background */
        .purple-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .purple-bg::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            animation: float1 20s infinite alternate;
        }

        .purple-bg::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            animation: float2 25s infinite alternate;
        }

        @keyframes float1 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-100px, -100px) scale(1.5); }
        }

        @keyframes float2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(100px, 100px) scale(1.5); }
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;
            background: rgba(18, 18, 28, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo i {
            background: linear-gradient(135deg, #f0abfc, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 10px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: #d8b4fe;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 16px;
        }

        .nav-links a:hover {
            color: #f0abfc;
            text-shadow: 0 0 10px rgba(147, 51, 234, 0.5);
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .btn-outline {
            padding: 10px 25px;
            border: 2px solid rgba(147, 51, 234, 0.5);
            border-radius: 30px;
            color: #d8b4fe;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 600;
        }

        .btn-outline:hover {
            border-color: #a855f7;
            background: rgba(147, 51, 234, 0.1);
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
            color: white;
        }

        .btn-primary {
            padding: 10px 25px;
            background: linear-gradient(135deg, #9333ea, #c084fc);
            border-radius: 30px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(147, 51, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(147, 51, 234, 0.6);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 0 50px;
            margin-top: 80px;
        }

        .hero-content {
            flex: 1;
            max-width: 600px;
        }

        .hero-content h1 {
            font-size: 64px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-content h1 span {
            background: linear-gradient(135deg, #f0abfc, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            font-size: 72px;
        }

        .hero-content p {
            font-size: 18px;
            color: #d8b4fe;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
        }

        .btn-large {
            padding: 15px 40px;
            font-size: 18px;
        }

        .hero-image {
            flex: 1;
            position: relative;
        }

        .hero-image img {
            width: 100%;
            max-width: 500px;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Features Section */
        .features {
            padding: 100px 50px;
            background: rgba(18, 18, 28, 0.5);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(147, 51, 234, 0.3);
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 48px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-title p {
            color: #d8b4fe;
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: rgba(147, 51, 234, 0.1);
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(147, 51, 234, 0.3);
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: #a855f7;
            box-shadow: 0 20px 40px rgba(147, 51, 234, 0.3);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(147, 51, 234, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 35px;
            color: #f0abfc;
            border: 1px solid rgba(147, 51, 234, 0.5);
        }

        .feature-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: white;
        }

        .feature-card p {
            color: #d8b4fe;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            padding: 80px 50px;
            display: flex;
            justify-content: space-around;
            max-width: 1000px;
            margin: 0 auto;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            background: linear-gradient(135deg, #f0abfc, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #d8b4fe;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* About Section */
        .about {
            padding: 100px 50px;
            background: rgba(18, 18, 28, 0.5);
            backdrop-filter: blur(10px);
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .about-content h2 {
            font-size: 48px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .about-content p {
            color: #d8b4fe;
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .about-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }

        .about-stat-item {
            text-align: left;
        }

        .about-stat-number {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #f0abfc, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .about-stat-label {
            color: #d8b4fe;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .about-image {
            position: relative;
        }

        .about-image img {
            width: 100%;
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        /* Mission/Vision Cards */
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 50px;
        }

        .mission-card, .vision-card {
            background: rgba(147, 51, 234, 0.1);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            backdrop-filter: blur(10px);
        }

        .mission-card i, .vision-card i {
            font-size: 40px;
            background: linear-gradient(135deg, #f0abfc, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .mission-card h3, .vision-card h3 {
            font-size: 28px;
            margin-bottom: 15px;
            color: white;
        }

        .mission-card p, .vision-card p {
            color: #d8b4fe;
            line-height: 1.6;
        }

        /* Contact Section */
        .contact {
            padding: 100px 50px;
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
        }

        .contact-info h2 {
            font-size: 48px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .contact-info p {
            color: #d8b4fe;
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 40px;
        }

        .contact-details {
            margin-top: 30px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .contact-item i {
            width: 50px;
            height: 50px;
            background: rgba(147, 51, 234, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #f0abfc;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        .contact-item h4 {
            color: white;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .contact-item p {
            color: #d8b4fe;
            font-size: 16px;
            margin: 0;
        }

        .contact-form {
            background: rgba(147, 51, 234, 0.1);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            backdrop-filter: blur(10px);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .form-group textarea {
            height: 150px;
            resize: none;
        }

        .btn-submit {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(147, 51, 234, 0.6);
        }

        /* Map Section */
        .map-section {
            padding: 0 50px 100px;
        }

        .map-container {
            max-width: 1200px;
            margin: 0 auto;
            height: 400px;
            background: rgba(147, 51, 234, 0.1);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            overflow: hidden;
        }

        .map-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d8b4fe;
            font-size: 18px;
        }

        .map-placeholder i {
            font-size: 60px;
            color: #f0abfc;
            margin-bottom: 20px;
        }

        /* CTA Section */
        .cta {
            padding: 100px 50px;
            text-align: center;
        }

        .cta-content {
            max-width: 700px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: 48px;
            margin-bottom: 20px;
            color: white;
        }

        .cta p {
            color: #d8b4fe;
            font-size: 18px;
            margin-bottom: 40px;
        }

        .btn-cta {
            padding: 18px 50px;
            font-size: 20px;
        }

        /* Footer */
        .footer {
            padding: 50px;
            background: rgba(18, 18, 28, 0.9);
            border-top: 1px solid rgba(147, 51, 234, 0.3);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer-section h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .footer-section a {
            color: #d8b4fe;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: #f0abfc;
        }

        .footer-section p {
            color: #d8b4fe;
            line-height: 1.6;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(147, 51, 234, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f0abfc;
            font-size: 18px;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(147, 51, 234, 0.3);
            color: #d8b4fe;
        }
    </style>
</head>
<body>
    <div class="purple-bg"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-heartbeat"></i> MediConnect
        </div>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <div class="nav-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="pages/dashboard-patient.php" class="btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="pages/login.php" class="btn-outline">Login</a>
                    <a href="pages/register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>
                Healthcare at Your
                <span>Fingertips</span>
            </h1>
            <p>Connect with top doctors from the comfort of your home. AI-powered symptom checker and smart medication reminders.</p>
            <div class="hero-buttons">
                <a href="pages/register.php" class="btn-primary btn-large">Get Started</a>
                <a href="#features" class="btn-outline btn-large">Learn More</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 500 500'%3E%3Ccircle cx='250' cy='250' r='200' fill='%23933bea20'/%3E%3Cpath d='M250 150 L250 350 M150 250 L350 250' stroke='%23933ea' stroke-width='5' stroke-linecap='round'/%3E%3C/svg%3E" alt="Healthcare Illustration">
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-title">
            <h2>Why Choose MediConnect?</h2>
            <p>Experience healthcare reimagined with our innovative features</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-video"></i>
                </div>
                <h3>Video Consultations</h3>
                <p>Connect with specialists via HD video calls from anywhere, anytime.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <h3>AI Health Assistant</h3>
                <p>24/7 symptom analysis and personalized health recommendations.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-pills"></i>
                </div>
                <h3>Smart Reminders</h3>
                <p>Never miss your medications with intelligent reminder system.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-file-medical"></i>
                </div>
                <h3>Digital Records</h3>
                <p>Access your medical history and prescriptions anytime.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>Easy Booking</h3>
                <p>Schedule appointments with just a few clicks.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure & Private</h3>
                <p>Your health data is encrypted and completely private.</p>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <div class="stats">
        <div class="stat-item">
            <div class="stat-number">100+</div>
            <div class="stat-label">Happy Patients</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">50+</div>
            <div class="stat-label">Expert Doctors</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">24/7</div>
            <div class="stat-label">AI Support</div>
        </div>
    </div>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="about-container">
            <div class="about-content">
                <h2>About MediConnect</h2>
                <p>Founded in 2026, MediConnect is Sri Lanka's fastest-growing telemedicine platform dedicated to making quality healthcare accessible to everyone, everywhere.</p>
                <p>Our team of passionate healthcare professionals and technologists work tirelessly to bridge the gap between patients and doctors using cutting-edge AI technology and secure video consultations.</p>
                
                <div class="about-stats">
                    <div class="about-stat-item">
                        <div class="about-stat-number">50+</div>
                        <div class="about-stat-label">Cities Covered</div>
                    </div>
                    <div class="about-stat-item">
                        <div class="about-stat-number">98%</div>
                        <div class="about-stat-label">Patient Satisfaction</div>
                    </div>
                    <div class="about-stat-item">
                        <div class="about-stat-number">24/7</div>
                        <div class="about-stat-label">Support Available</div>
                    </div>
                    <div class="about-stat-item">
                        <div class="about-stat-number">15min</div>
                        <div class="about-stat-label">Average Response</div>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 500 500'%3E%3Crect width='500' height='500' fill='%23933bea10'/%3E%3Ccircle cx='250' cy='200' r='80' fill='%23933bea20'/%3E%3Ccircle cx='250' cy='250' r='100' fill='%23933bea15'/%3E%3Cpath d='M150 350 L350 350' stroke='%23933ea' stroke-width='10' stroke-linecap='round'/%3E%3C/svg%3E" alt="About Us">
            </div>
        </div>

        <div class="mission-vision">
            <div class="mission-card">
                <i class="fas fa-bullseye"></i>
                <h3>Our Mission</h3>
                <p>To democratize healthcare by making quality medical consultations accessible, affordable, and convenient for every Sri Lankan, regardless of their location.</p>
            </div>
            <div class="vision-card">
                <i class="fas fa-eye"></i>
                <h3>Our Vision</h3>
                <p>To create a future where distance is no barrier to healthcare, and every patient receives personalized, timely medical attention powered by AI.</p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="contact-container">
            <div class="contact-info">
                <h2>Get In Touch</h2>
                <p>Have questions about our services? Want to partner with us? Our team is here to help. Reach out and we'll get back to you within 24 hours.</p>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Visit Us</h4>
                            <p>No. 123, Galle Road, Colombo 03, Sri Lanka</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <h4>Call Us</h4>
                            <p>+94 11 234 5678</p>
                            <p>+94 77 123 4567 (24/7 Emergency)</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email Us</h4>
                            <p>info@mediconnect.lk</p>
                            <p>support@mediconnect.lk</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Working Hours</h4>
                            <p>Monday - Friday: 8:00 AM - 8:00 PM</p>
                            <p>Saturday - Sunday: 9:00 AM - 5:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <form action="#" method="POST">
                    <div class="form-group">
                        <input type="text" placeholder="Your Full Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" placeholder="Your Email Address" required>
                    </div>
                    <div class="form-group">
                        <input type="text" placeholder="Subject">
                    </div>
                    <div class="form-group">
                        <textarea placeholder="Your Message" required></textarea>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="map-container">
            <div class="map-placeholder">
                <div style="text-align: center;">
                    <i class="fas fa-map-marked-alt"></i>
                    <p> Our Location: Colombo, Sri Lanka</p>
                    <p style="font-size: 14px; margin-top: 10px;">Interactive Map Here</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-content">
            <h2>Ready to Transform Your Healthcare?</h2>
            <p>Join thousands of patients who already trust MediConnect for their healthcare needs.</p>
            <a href="pages/register.php" class="btn-primary btn-large btn-cta">Create Free Account</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>MediConnect</h3>
                <p style="color: #d8b4fe;">Your health, our priority. Revolutionizing healthcare through technology.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="#home">Home</a>
                <a href="#features">Features</a>
                <a href="#about">About Us</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="footer-section">
                <h3>Our Services</h3>
                <a href="#">Video Consultations</a>
                <a href="#">AI Health Assistant</a>
                <a href="#">Medication Reminders</a>
                <a href="#">Medical Records</a>
            </div>
            <div class="footer-section">
                <h3>Contact Info</h3>
                <p><i class="fas fa-map-marker-alt" style="margin-right: 10px; color: #f0abfc;"></i> Colombo, Sri Lanka</p>
                <p><i class="fas fa-phone" style="margin-right: 10px; color: #f0abfc;"></i> +94 11 234 5678</p>
                <p><i class="fas fa-envelope" style="margin-right: 10px; color: #f0abfc;"></i> info@mediconnect.lk</p>
            </div>
        </div>
        <div class="copyright">
            © 2026 MediConnect. All rights reserved.
        </div>
    </footer>
</body>
</html>