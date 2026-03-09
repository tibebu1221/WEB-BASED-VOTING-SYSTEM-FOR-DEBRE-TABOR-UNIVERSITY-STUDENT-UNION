<?php
// Include and start session ONCE at the very top.
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
    <title>Help Center - Online Voting System</title>
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

        /* Main Content Layout */
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
            height: 200px;
            object-fit: cover;
        }

        .info-card {
            padding: 20px;
            background: var(--white);
        }

        .info-card h3 {
            color: var(--primary-blue);
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card h3 i {
            color: var(--accent-green);
        }

        .date-display {
            text-align: center;
            padding: 12px;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            border-radius: 8px;
            margin-bottom: 8px;
            border-left: 4px solid var(--accent-green);
        }

        .date-range {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .date-start, .date-end {
            font-weight: 600;
            color: var(--text-dark);
        }

        .date-end {
            color: var(--primary-blue);
        }

        .more-info {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 25px;
            background: var(--primary-blue);
            color: var(--white);
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: var(--transition);
            width: 100%;
            text-align: center;
        }

        .more-info:hover {
            background: #0d1e5a;
            transform: translateY(-2px);
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
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
        }

        .page-header h1 {
            color: var(--primary-blue);
            font-size: 2.2rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-blue), #4f6bc9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-header .subtitle {
            color: var(--text-light);
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Help Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .feature-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            border: 1px solid var(--medium-gray);
            transition: var(--transition);
            text-align: center;
        }

        .feature-card:hover {
            border-color: var(--accent-green);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(81, 163, 81, 0.1);
        }

        .feature-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 8px;
            background: var(--light-gray);
            padding: 10px;
        }

        .feature-title {
            color: var(--primary-blue);
            font-size: 1.3rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .feature-title i {
            color: var(--accent-green);
        }

        .feature-description {
            color: var(--text-light);
            line-height: 1.7;
        }

        /* FAQ Section */
        .faq-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid var(--medium-gray);
        }

        .faq-title {
            color: var(--primary-blue);
            font-size: 1.5rem;
            margin-bottom: 25px;
            text-align: center;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .faq-item {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 20px;
            transition: var(--transition);
        }

        .faq-item:hover {
            background: #e8f4e8;
        }

        .faq-question {
            color: var(--secondary-blue);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .faq-question i {
            color: var(--accent-green);
        }

        .faq-answer {
            color: var(--text-light);
            font-size: 0.95rem;
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
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .feature-card {
                padding: 20px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
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
                  <!--  <img src="img/log.png" alt="Online Voting System" class="university-logo">-->
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
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                <li class="active"><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="contacts.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
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
                <h3><i class="fas fa-user-plus"></i> Voter Registration</h3>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM voter_reg_date LIMIT 1");
                if ($row = mysqli_fetch_assoc($result)):
                ?>
                <div class="date-display">
                    <div class="date-range">
                        <span class="date-start"><?php echo htmlspecialchars($row['start']); ?></span>
                        <span><i class="fas fa-arrow-down"></i> to <i class="fas fa-arrow-down"></i></span>
                        <span class="date-end"><?php echo htmlspecialchars($row['end']); ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div class="date-display">No dates set</div>
                <?php endif; ?>
            </div>

            <div class="info-card sidebar-card">
                <h3><i class="fas fa-user-tie"></i> Candidate Registration</h3>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM candidate_reg_date LIMIT 1");
                if ($row = mysqli_fetch_assoc($result)):
                ?>
                <div class="date-display">
                    <div class="date-range">
                        <span class="date-start"><?php echo htmlspecialchars($row['start']); ?></span>
                        <span><i class="fas fa-arrow-down"></i> to <i class="fas fa-arrow-down"></i></span>
                        <span class="date-end"><?php echo htmlspecialchars($row['end']); ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div class="date-display">No dates set</div>
                <?php endif; ?>
            </div>

            <div class="info-card sidebar-card">
                <h3><i class="fas fa-calendar-alt"></i> Election Date</h3>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM election_date LIMIT 1");
                if ($row = mysqli_fetch_assoc($result)):
                ?>
                <div class="date-display">
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary-blue);">
                        <?php echo htmlspecialchars($row['date']); ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="date-display">To be announced</div>
                <?php endif; ?>
            </div>

            <a href="more.php" class="more-info sidebar-card">
                <i class="fas fa-info-circle"></i> More Information
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-question-circle"></i> Help Center</h1>
                <p class="subtitle">Everything you need to know about using our Online Voting System</p>
            </div>

            <div class="features-grid">
                <!-- Home Page Feature -->
                <div class="feature-card">
                    <img src="deve/H.PNG" alt="Home Page Preview" class="feature-image">
                    <h3 class="feature-title">
                        <i class="fas fa-home"></i> Home Page
                    </h3>
                    <p class="feature-description">
                        The central hub of our voting system. From here, users can navigate to all 
                        sections of the platform. Registered users can log in to access personalized 
                        features and voting options.
                    </p>
                </div>

                <!-- Login Page Feature -->
                <div class="feature-card">
                    <img src="deve/l.png" alt="Login Page Preview" class="feature-image">
                    <h3 class="feature-title">
                        <i class="fas fa-sign-in-alt"></i> Login Page
                    </h3>
                    <p class="feature-description">
                        Your secure gateway to the voting system. Enter your username and password 
                        to access your account. This page ensures only authorized users can 
                        participate in the voting process.
                    </p>
                </div>

                <!-- Voter Page Feature -->
                <div class="feature-card">
                    <img src="deve/vO.png" alt="Voter Page Preview" class="feature-image">
                    <h3 class="feature-title">
                        <i class="fas fa-vote-yea"></i> Voter Dashboard
                    </h3>
                    <p class="feature-description">
                        After successful login, voters are directed to their personal dashboard. 
                        From here, they can review candidates, read their platforms, and cast 
                        their vote securely and confidentially.
                    </p>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <h2 class="faq-title">Frequently Asked Questions</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h4 class="faq-question">
                            <i class="fas fa-key"></i> How do I register to vote?
                        </h4>
                        <p class="faq-answer">
                            Visit the registration page during the registration period shown in the sidebar. 
                            You'll need your student ID and other required information.
                        </p>
                    </div>

                    <div class="faq-item">
                        <h4 class="faq-question">
                            <i class="fas fa-lock"></i> Is my vote secure?
                        </h4>
                        <p class="faq-answer">
                            Yes, our system uses multiple security layers including encryption and 
                            authentication to ensure your vote remains confidential and secure.
                        </p>
                    </div>

                    <div class="faq-item">
                        <h4 class="faq-question">
                            <i class="fas fa-clock"></i> When will results be announced?
                        </h4>
                        <p class="faq-answer">
                            Election results are typically announced within 24 hours after the voting 
                            period ends. Check the Results page for official announcements.
                        </p>
                    </div>

                    <div class="faq-item">
                        <h4 class="faq-question">
                            <i class="fas fa-user-check"></i> Can I change my vote?
                        </h4>
                        <p class="faq-answer">
                            No, once a vote is submitted, it cannot be changed to maintain the 
                            integrity of the election process.
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
                For additional help, contact us through the Contact Us page.
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