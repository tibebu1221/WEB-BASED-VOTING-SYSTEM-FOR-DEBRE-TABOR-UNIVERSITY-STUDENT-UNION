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
    <title>Contact Us - Online Voting System</title>
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
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid var(--light-gray);
        }

        .page-header h1 {
            color: var(--primary-blue);
            font-size: 2.2rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-blue), #4f6bc9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header .subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 700px;
            line-height: 1.7;
        }

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .contact-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            border: 1px solid var(--medium-gray);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--accent-green);
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--accent-green);
        }

        .contact-icon {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .contact-title {
            color: var(--primary-blue);
            font-size: 1.4rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .contact-list {
            list-style: none;
            padding: 0;
        }

        .contact-list li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
            display: flex;
            align-items: center;
        }

        .contact-list li i {
            position: absolute;
            left: 0;
            color: var(--accent-green);
            font-size: 1.1rem;
        }

        .contact-label {
            font-weight: 600;
            color: var(--text-dark);
            min-width: 150px;
        }

        .contact-value {
            color: var(--text-light);
            flex: 1;
        }

        .phone-number {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Map Section */
        .map-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--medium-gray);
        }

        .map-section h3 {
            color: var(--primary-blue);
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .map-placeholder {
            background: linear-gradient(135deg, #e6f0ff 0%, #d4e4ff 100%);
            border-radius: var(--border-radius);
            padding: 40px;
            text-align: center;
            color: var(--text-light);
            border: 2px dashed var(--medium-gray);
        }

        .map-placeholder i {
            font-size: 3rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
            opacity: 0.7;
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
            
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .contact-card {
                padding: 20px;
            }
            
            .contact-list li {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .contact-label {
                min-width: auto;
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
                   <!-- <img src="img/log.png" alt="Online Voting System" class="university-logo">-->
                    <h1>Debre Tabor University Student Union   Voting System</h1>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li class="active"><a href="contacts.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
               <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->
                <li><a href="advert.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
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
                    <span class="date-label">Election Date for 2017</span>
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
                <h1><i class="fas fa-headset"></i> Contact Us</h1>
                <p class="subtitle">
                    Get in touch with our team for any questions, concerns, or support regarding 
                    the Online Voting System. We're here to help you!
                </p>
            </div>

            <div class="contact-grid">
                <!-- System Admin Card -->
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3 class="contact-title">System Administrator</h3>
                    <ul class="contact-list">
                        <li>
                            <i class="fas fa-phone"></i>
                            <span class="contact-label">Phone:</span>
                            <span class="contact-value phone-number">+251 581 41 04 95</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span class="contact-label">Hours:</span>
                            <span class="contact-value">Mon-Fri: 8:00 AM - 5:00 PM</span>
                        </li>
                        <li>
                            <i class="fas fa-tasks"></i>
                            <span class="contact-label">Responsibilities:</span>
                            <span class="contact-value">System maintenance, security, technical support</span>
                        </li>
                    </ul>
                </div>

                <!-- Election Officer Card -->
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="contact-title">Election Officer</h3>
                    <ul class="contact-list">
                        <li>
                            <i class="fas fa-fax"></i>
                            <span class="contact-label">Fax:</span>
                            <span class="contact-value phone-number">+251 581 41 2260</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span class="contact-label">Hours:</span>
                            <span class="contact-value">Mon-Fri: 8:30 AM - 4:30 PM</span>
                        </li>
                        <li>
                            <i class="fas fa-tasks"></i>
                            <span class="contact-label">Responsibilities:</span>
                            <span class="contact-value">Overseeing election process, official communications</span>
                        </li>
                    </ul>
                </div>

                <!-- Discipline Committee Card -->
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h3 class="contact-title">Discipline Committee</h3>
                    <ul class="contact-list">
                        <li>
                            <i class="fas fa-mobile-alt"></i>
                            <span class="contact-label">Mobile:</span>
                            <span class="contact-value phone-number">09 37 88 41 56</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span class="contact-label">Hours:</span>
                            <span class="contact-value">Mon-Sat: 9:00 AM - 6:00 PM</span>
                        </li>
                        <li>
                            <i class="fas fa-tasks"></i>
                            <span class="contact-label">Responsibilities:</span>
                            <span class="contact-value">Enforcing election rules, handling complaints, ensuring fair play</span>
                        </li>
                    </ul>
                </div>

                <!-- General Contact Card -->
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <h3 class="contact-title">University Contact</h3>
                    <ul class="contact-list">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span class="contact-label">Email:</span>
                            <span class="contact-value">dtu@dtu.edu.et</span>
                        </li>
                        <li>
                            <i class="fas fa-globe"></i>
                            <span class="contact-label">Website:</span>
                            <span class="contact-value">www.dtu.edu.et</span>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span class="contact-label">Address:</span>
                            <span class="contact-value">Debre Tabor University, Debre Tabor, Ethiopia</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Map Section -->
            <div class="map-section">
                <h3><i class="fas fa-map-marked-alt"></i> Our Location</h3>
                <div class="map-placeholder">
                    <i class="fas fa-map"></i>
                    <h4>Debre Tabor University</h4>
                    <p>Debre Tabor, Amhara Region, Ethiopia</p>
                    <p style="font-size: 0.9rem; margin-top: 10px;">
                        <i class="fas fa-info-circle"></i> Map integration available soon
                    </p>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <p><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Debre Tabor University Student Union Election Commission. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-phone"></i> Contact Hours: Monday to Friday, 8:00 AM - 5:00 PM
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