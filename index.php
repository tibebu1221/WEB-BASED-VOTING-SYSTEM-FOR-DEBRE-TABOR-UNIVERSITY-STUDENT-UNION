<?php
session_start();
include("connection.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTUSU Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e3c72;
            --secondary: #2a5298;
            --accent: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 20px 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            padding: 5px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .university-title {
            color: white;
        }

        .university-title h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .university-title p {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Announcement Banner */
        .announcement-banner {
            background: linear-gradient(90deg, var(--dark), #34495e);
            color: white;
            padding: 15px 0;
            position: relative;
            overflow: hidden;
            margin-bottom: 2px;
        }

        .announcement-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            animation: scrollBanner 25s linear infinite;
            white-space: nowrap;
            padding: 0 20px;
        }

        @keyframes scrollBanner {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        .announcement-banner:hover .announcement-content {
            animation-play-state: paused;
        }

        .announcement-icon {
            font-size: 1.2rem;
            color: var(--accent);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Navigation */
        .navbar {
            background: var(--dark);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            justify-content: center;
            flex-wrap: wrap;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 18px 20px;
            display: block;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent);
        }

        .nav-link.active {
            background: var(--accent);
            color: white;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: white;
        }

        /* Hero Slider */
        .hero-section {
            padding: 40px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .hero-slider {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            position: relative;
            height: 500px;
        }

        .slider-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .slider-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
        }

        .slider-slide.active {
            opacity: 1;
        }

        .slider-slide:nth-child(1) { background-image: url('images/slider-1.JPG'); }
        .slider-slide:nth-child(2) { background-image: url('images/Cap.PNG'); }
        .slider-slide:nth-child(3) { background-image: url('images/4.JPG'); }
        .slider-slide:nth-child(4) { background-image: url('images/dtu.JPG'); }
        .slider-slide:nth-child(5) { background-image: url('images/dtc.JPG'); }
        .slider-slide:nth-child(6) { background-image: url('images/Capture.JPG'); }
        .slider-slide:nth-child(7) { background-image: url('images/slider-6.jpg'); }
        .slider-slide:nth-child(8) { background-image: url('images/ethi.PNG'); }

        .slider-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            padding: 30px;
        }

        .slider-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .slider-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slider-btn:hover {
            background: var(--accent);
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .info-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 5px solid var(--accent);
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .info-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }

        .info-card-content {
            color: #555;
            line-height: 1.7;
        }

        .info-card-date {
            display: inline-block;
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            color: white;
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 15px;
            font-size: 1.1rem;
        }

        .more-link {
            display: inline-block;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
            transition: var(--transition);
        }

        .more-link:hover {
            color: var(--secondary);
            transform: translateX(5px);
        }

        /* Content Section */
        .content-section {
            background: white;
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
        }

        .content-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 25px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .content-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, var(--accent), var(--secondary));
        }

        .content-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
            margin-bottom: 30px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .feature-card {
            text-align: center;
            padding: 25px;
            border-radius: var(--radius);
            background: #f8f9fa;
            transition: var(--transition);
        }

        .feature-card:hover {
            background: linear-gradient(135deg, var(--light), #e3f2fd);
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 15px;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 60px 20px;
            text-align: center;
            margin-top: 60px;
            border-radius: var(--radius);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-50px, -50px) rotate(360deg); }
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-3px);
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 20px;
            margin-top: 60px;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer-section h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--accent);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #95a5a6;
            font-size: 0.9rem;
        }

        .copyright a {
            color: var(--accent);
            text-decoration: none;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .header-container {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .hero-slider {
                height: 400px;
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-link {
                padding: 12px 15px;
                width: 100%;
                text-align: center;
            }
            
            .hero-slider {
                height: 300px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
            
            .announcement-content {
                animation: scrollBanner 15s linear infinite;
            }
        }

        @media (max-width: 480px) {
            .content-section {
                padding: 25px 20px;
            }
            
            .info-card {
                padding: 20px;
            }
            
            .cta-title {
                font-size: 2rem;
            }
            
            .content-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <div class="logo-container">
                <img src="img/logo.JPG" alt="DTU Logo" class="logo">
                <div class="university-title">
                    <h1>Debre Tabor University</h1>
                    <p>Student Union Online Voting System</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Announcement Banner -->
    <div class="announcement-banner">
        <div class="announcement-content">
            <i class="fas fa-bullhorn announcement-icon"></i>
            <span>Welcome to Debre Tabor University Student Union Voting System! Cast your vote today and shape the future of our campus.</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link active">Home</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link">About Us</a></li>
                <li class="nav-item"><a href="help.php" class="nav-link">Help</a></li>
                <li class="nav-item"><a href="contacts.php" class="nav-link">Contact Us</a></li>
               <!-- <li class="nav-item"><a href="h_result.php" class="nav-link">Result</a></li>-->
                <li class="nav-item"><a href="advert.php" class="nav-link">Advert</a></li>
                <li class="nav-item"><a href="candidate.php" class="nav-link">Candidates</a></li>
                <li class="nav-item"><a href="vote.php" class="nav-link">Vote</a></li>
                <li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Slider -->
    <section class="hero-section">
        <div class="hero-slider">
            <div class="slider-container">
                <?php for ($i = 1; $i <= 8; $i++): ?>
                <div class="slider-slide <?= $i === 1 ? 'active' : '' ?>" id="slide-<?= $i ?>"></div>
                <?php endfor; ?>
                <div class="slider-overlay">
                    <h2 style="font-size: 2rem; margin-bottom: 10px;">Shape Your Campus Future</h2>
                    <p>Participate in democratic elections for your student union representatives</p>
                </div>
                <div class="slider-controls">
                    <button class="slider-btn" onclick="prevSlide()">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="slider-btn" onclick="nextSlide()">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Sidebar with Dates -->
        <aside class="sidebar">
            <?php
            // Voter Registration Date
            $result = mysqli_query($conn, "SELECT * FROM voter_reg_date");
            if($result && mysqli_num_rows($result) > 0):
                while($row = mysqli_fetch_array($result)):
            ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="info-card-title">Voter Registration</div>
                </div>
                <div class="info-card-content">
                    <p>Voter registration for the 2024 academic year is now open. Make sure to register within the specified dates to participate in the elections.</p>
                    <div class="info-card-date"><?= htmlspecialchars($row['start']) ?> - <?= htmlspecialchars($row['end']) ?></div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="info-card-title">Voter Registration</div>
                </div>
                <div class="info-card-content">
                    <p>Registration dates will be announced soon. Please check back later.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php
            // Candidate Registration Date
            $result = mysqli_query($conn, "SELECT * FROM candidate_reg_date");
            if($result && mysqli_num_rows($result) > 0):
                while($row = mysqli_fetch_array($result)):
            ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-card-title">Candidate Registration</div>
                </div>
                <div class="info-card-content">
                    <p>Interested in representing your fellow students? Register as a candidate during the specified dates.</p>
                    <div class="info-card-date"><?= htmlspecialchars($row['start']) ?> - <?= htmlspecialchars($row['end']) ?></div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-card-title">Candidate Registration</div>
                </div>
                <div class="info-card-content">
                    <p>Candidate registration dates will be announced soon.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php
            // Election Date
            $result = mysqli_query($conn, "SELECT * FROM election_date");
            if($result && mysqli_num_rows($result) > 0):
                while($row = mysqli_fetch_array($result)):
            ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="info-card-title">Election Day</div>
                </div>
                <div class="info-card-content">
                    <p>Mark your calendar! This is the day when students exercise their democratic right to vote for their representatives.</p>
                    <div class="info-card-date"><?= htmlspecialchars($row['date']) ?></div>
                    <a href="more.php" class="more-link">
                        More Information <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="info-card-title">Election Day</div>
                </div>
                <div class="info-card-content">
                    <p>Election date will be announced soon.</p>
                    <a href="more.php" class="more-link">
                        More Information <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Main Content Area -->
        <section class="content-section">
            <h2 class="content-title">ONLINE VOTING SYSTEM</h2>
            <p class="content-description">
                Welcome to Debre Tabor University Student Union Online Voting System. Our platform provides a secure, transparent, and efficient way for students to participate in democratic elections for their student union representatives.
            </p>
            
            <p class="content-description">
                Debre Tabor University offers a comprehensive range of academic disciplines encompassing technology, agriculture, medicine and health sciences, business and economics, social science and the humanities, natural and computational sciences, law, information and communication technology, and biotechnology. Within this diverse academic landscape, both undergraduate and graduate programs are made available to students, thereby affording them the opportunity to pursue education at varying levels of depth and specialization.
            </p>

            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <h3 class="feature-title">Secure Voting</h3>
                    <p>Advanced security measures ensure your vote is confidential and tamper-proof.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt feature-icon"></i>
                    <h3 class="feature-title">Accessible</h3>
                    <p>Vote from anywhere using any device with internet access.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line feature-icon"></i>
                    <h3 class="feature-title">Real-time Results</h3>
                    <p>View election results as they come in after voting closes.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-check feature-icon"></i>
                    <h3 class="feature-title">Easy Registration</h3>
                    <p>Simple and quick registration process for both voters and candidates.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Call to Action -->
    <section class="cta-section">
        <h2 class="cta-title">Ready to Make Your Voice Heard?</h2>
        <p style="font-size: 1.2rem; max-width: 800px; margin: 0 auto; position: relative; z-index: 1;">
            Participate in the democratic process and help shape the future of Debre Tabor University student community.
        </p>
        <div class="cta-buttons">
            <a href="vote.php" class="btn btn-primary">
                <i class="fas fa-vote-yea"></i> Vote Now
            </a>
            <a href="candidate.php" class="btn btn-outline">
                <i class="fas fa-users"></i> View Candidates
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="help.php">Help & Support</a></li>
                    <li><a href="contacts.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Election Info</h3>
                <ul class="footer-links">
                    <li><a href="candidate.php">Candidates</a></li>
                    <li><a href="h_result.php">Election Results</a></li>
                    <li><a href="advert.php">Announcements</a></li>
                    <li><a href="more.php">More Information</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Resources</h3>
                <ul class="footer-links">
                    <li><a href="login.php">Login Portal</a></li>
                    <li><a href="vote.php">Voting Portal</a></li>
                    <li><a href="dev.php">Developer Team</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?= date('Y') ?> Debre Tabor University Student Union. All rights reserved.</p>
            <p><a href="dev.php">Developer Team</a></p>
        </div>
    </footer>

    <script>
        // Image Slider
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slider-slide');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            if (index >= totalSlides) currentSlide = 0;
            else if (index < 0) currentSlide = totalSlides - 1;
            else currentSlide = index;
            slides[currentSlide].classList.add('active');
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        // Auto slide every 5 seconds
        setInterval(nextSlide, 5000);

        // Initialize first slide
        showSlide(0);

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.2)';
                navbar.style.background = 'rgba(44, 62, 80, 0.95)';
            } else {
                navbar.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.1)';
                navbar.style.background = 'var(--dark)';
            }
        });
    </script>
</body>
</html>

<?php
// Close connection
mysqli_close($conn);
?>