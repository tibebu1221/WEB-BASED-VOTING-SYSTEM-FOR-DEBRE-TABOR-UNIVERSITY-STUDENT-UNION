<?php
include("connection.php");
session_start();

// Check connection
if (isset($conn) && !$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set security headers
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #1a2a6c;
            --secondary-blue: #2F4F4F;
            --accent-green: #51a351;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e6ef;
            --dark-gray: #4a5568;
            --text-dark: #2d3748;
            --text-light: #718096;
            --white: #ffffff;
            --sidebar-width: 280px;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
            padding: 1.2rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .logo {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .university-logo {
            height: 110px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        /* Navigation */
        .navbar {
            background: var(--secondary-blue);
            padding: 0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            flex-wrap: wrap;
            margin: 0;
            padding: 0;
        }

        .nav-menu li {
            position: relative;
        }

        .nav-menu a {
            color: var(--white);
            text-decoration: none;
            padding: 16px 22px;
            display: block;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }

        .nav-menu .active a {
            background: var(--accent-green);
            color: var(--white);
            border-radius: 6px;
            margin: 4px 2px;
        }

        /* Main Layout */
        .main-layout {
            display: flex;
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            gap: 30px;
        }

        @media (max-width: 992px) {
            .main-layout {
                flex-direction: column;
            }
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            flex-shrink: 0;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
            }
        }

        .sidebar-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .sidebar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .sidebar-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-bottom: 3px solid var(--accent-green);
        }

        .info-card {
            padding: 25px;
            background: var(--white);
        }

        .info-card h3 {
            color: var(--primary-blue);
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }

        .info-card h3 i {
            color: var(--accent-green);
        }

        .date-display {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid rgba(26, 42, 108, 0.1);
        }

        .date-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 5px;
            display: block;
        }

        .date-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-blue);
            display: block;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 35px;
            box-shadow: var(--box-shadow);
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid var(--light-gray);
        }

        .page-header h1 {
            color: var(--primary-blue);
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-blue), #4f6bc9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-header .subtitle {
            color: var(--text-light);
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
        }

        /* Mission Section */
        .mission-section {
            background: linear-gradient(135deg, #f8faff 0%, #f0f5ff 100%);
            border-radius: var(--border-radius);
            padding: 40px;
            margin-bottom: 40px;
            border-left: 5px solid var(--accent-green);
        }

        .mission-section h2 {
            color: var(--primary-blue);
            font-size: 1.8rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mission-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-dark);
        }

        .highlight {
            color: var(--accent-green);
            font-weight: 600;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }

        .feature-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            border: 1px solid var(--medium-gray);
            transition: var(--transition);
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--accent-green);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 20px;
        }

        .feature-title {
            color: var(--primary-blue);
            font-size: 1.4rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .feature-description {
            color: var(--text-light);
            line-height: 1.7;
        }

        /* Values Section */
        .values-section {
            margin-top: 50px;
            padding: 40px;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            border-radius: var(--border-radius);
        }

        .values-section h2 {
            color: var(--primary-blue);
            font-size: 1.8rem;
            margin-bottom: 30px;
            text-align: center;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .value-item {
            text-align: center;
            padding: 25px;
            background: var(--white);
            border-radius: 10px;
            transition: var(--transition);
        }

        .value-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .value-icon {
            font-size: 2rem;
            color: var(--accent-green);
            margin-bottom: 15px;
        }

        .value-title {
            color: var(--primary-blue);
            font-size: 1.2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .value-description {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background: var(--secondary-blue);
            color: var(--white);
            padding: 25px 0;
            margin-top: 50px;
        }

        .footer-content {
            text-align: center;
        }

        .footer p {
            margin: 0;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                justify-content: center;
                text-align: center;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            .main-content {
                padding: 25px 20px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .mission-section {
                padding: 25px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .nav-menu {
                flex-direction: column;
                width: 100%;
            }
            
            .nav-menu li {
                width: 100%;
            }
            
            .nav-menu a {
                text-align: center;
            }
            
            .logo-section {
                flex-direction: column;
                gap: 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="img/logo.JPG" alt="Debre Tabor University Logo" class="logo">
                   <!-- <img src="img/adtusu.png" alt="Online Voting System" class="university-logo">-->
                    <h1>Debre Tabor University
Student Union   Voting System</h1>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li class="active"><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="contacts.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
               <!-- <li class="nav-item"><a href="h_result.php" class="nav-link">Result</a></li>-->
                <li><a href="advert.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="dev.php"><i class="fas fa-code"></i> Developer</a></li>
                <li><a href="candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote Now</a></li>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Layout -->
    <div class="main-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <img src="deve/dt.PNG" alt="Debre Tabor University Campus" class="sidebar-image">
            </div>

            <div class="info-card sidebar-card">
                <h3><i class="fas fa-calendar-alt"></i> Election Date</h3>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM election_date LIMIT 1");
                if ($row = mysqli_fetch_assoc($result)):
                ?>
                <div class="date-display">
                    <span class="date-label">Upcoming Election</span>
                    <span class="date-value"><?php echo htmlspecialchars($row['date']); ?></span>
                </div>
                <?php else: ?>
                <div class="date-display">
                    <span class="date-value">To be announced</span>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-university"></i> About Us</h1>
                <p class="subtitle">
                    Learn about our mission, values, and the innovative platform that's revolutionizing 
                    student elections at Debre Tabor University.
                </p>
            </div>

            <!-- Mission Section -->
            <div class="mission-section">
                <h2><i class="fas fa-bullseye"></i> Our Mission</h2>
                <p class="mission-text">
                    The <span class="highlight">Online Voting System for Debre Tabor University Student Union</span> 
                    is designed to provide a <span class="highlight">secure, efficient, and reliable</span> platform 
                    for conducting elections online. Our mission is to empower student democracy through 
                    technology, making voting accessible, transparent, and convenient for every student.
                </p>
            </div>

            <!-- Features Grid -->
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Secure & Reliable</h3>
                    <p class="feature-description">
                        Advanced security measures including encryption, authentication, 
                        and audit trails to ensure the integrity of every vote.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="feature-title">Fast & Efficient</h3>
                    <p class="feature-description">
                        Streamlined voting process that saves time and resources while 
                        providing instant results and real-time updates.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Accessible Anywhere</h3>
                    <p class="feature-description">
                        Vote from any device with internet access, making participation 
                        convenient for all students regardless of location.
                    </p>
                </div>
            </div>

            <!-- Values Section -->
            <div class="values-section">
                <h2><i class="fas fa-heart"></i> Our Core Values</h2>
                <div class="values-grid">
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3 class="value-title">Integrity</h3>
                        <p class="value-description">
                            Ensuring every vote is counted accurately and transparently.
                        </p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="value-title">Inclusivity</h3>
                        <p class="value-description">
                            Making voting accessible to every student, regardless of circumstances.
                        </p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3 class="value-title">Innovation</h3>
                        <p class="value-description">
                            Continuously improving our platform with cutting-edge technology.
                        </p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="value-title">Trust</h3>
                        <p class="value-description">
                            Building confidence in the electoral process through transparency.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <p><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Debre Tabor University Student Union Election Commission. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-vote-yea"></i> Empowering Student Democracy Through Technology
            </p>
        </div>
    </footer>

    <?php
    // Close database connection
    if (isset($conn)) {
        mysqli_close($conn);
    }
    ?>
</body>
</html>