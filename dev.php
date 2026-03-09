<?php
include("connection.php");
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System - Developers</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="menu.css">
    <link rel="stylesheet" href="themes/4/js-image-slider.css">
    <link rel="stylesheet" href="generic.css">
    <style>
        :root {
            --primary: #1E90FF;
            --primary-dark: #0066CC;
            --secondary: #2F4F4F;
            --accent: #4682B4;
            --light: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .logo {
            width: 180px;
            height: auto;
            border-radius: 4px;
        }

        .main-logo {
            width: 450px;
            height: auto;
        }

        .system-title h1 {
            color: white;
            font-size: 24px;
            text-align: center;
            margin-bottom: 5px;
        }

        /* Navigation */
        .nav-menu {
            background: var(--secondary);
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .nav-menu ul {
            display: flex;
            list-style: none;
            flex-wrap: wrap;
        }

        .nav-menu li {
            flex: 1;
            min-width: 120px;
        }

        .nav-menu a {
            display: block;
            color: var(--white);
            text-decoration: none;
            padding: 15px 20px;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-menu a:hover {
            background: var(--accent);
            transform: translateY(-2px);
        }

        .nav-menu li.active a {
            background: var(--primary);
            position: relative;
        }

        .nav-menu li.active a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 3px;
            background: var(--white);
            border-radius: 2px;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar */
        .sidebar {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .developer-photo {
            width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border: 3px solid var(--primary);
        }

        .election-date {
            background: linear-gradient(135deg, var(--light) 0%, #e3f2fd 100%);
            padding: 15px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary);
        }

        .election-date h3 {
            color: #B22222;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .date-display {
            font-size: 20px;
            font-weight: bold;
            color: var(--secondary);
            text-align: center;
            padding: 10px;
            background: var(--white);
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Content Area */
        .content-area {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow);
        }

        .page-title {
            color: var(--primary);
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light);
            position: relative;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: var(--primary);
        }

        /* Developer Grid */
        .developers-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 992px) {
            .developers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .developers-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Developer Card */
        .developer-card {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 25px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
        }

        .developer-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .developer-photo-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--white);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .developer-name {
            color: var(--primary);
            font-size: 22px;
            margin-bottom: 8px;
            font-weight: 600;
            line-height: 1.2;
        }

        .developer-title {
            color: var(--accent);
            font-size: 16px;
            font-style: italic;
            margin-bottom: 8px;
            min-height: 40px;
        }

        .developer-year {
            color: var(--secondary);
            font-size: 14px;
            font-weight: 500;
            background: var(--white);
            padding: 6px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .developer-quote {
            color: #555;
            font-style: italic;
            font-size: 14px;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            width: 100%;
        }

        /* Team Info Section - Updated without stats */
        .team-info {
            background: linear-gradient(135deg, var(--light) 0%, #e3f2fd 100%);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 40px;
            text-align: center;
        }

        .team-title {
            color: var(--primary);
            font-size: 24px;
            margin-bottom: 20px;
        }

        .team-description {
            color: var(--secondary);
            font-size: 16px;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
            color: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            margin-top: 30px;
            box-shadow: var(--shadow);
        }

        .copyright {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .logo-section {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .main-logo {
                width: 100%;
                max-width: 450px;
            }
            
            .nav-menu ul {
                flex-direction: column;
            }
            
            .nav-menu li {
                min-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0 10px;
            }
            
            .header {
                padding: 15px;
            }
            
            .content-area {
                padding: 20px;
            }
            
            .developer-photo-large {
                width: 120px;
                height: 120px;
            }
            
            .developers-grid {
                gap: 20px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .stagger-delay-1 { animation-delay: 0.1s; }
        .stagger-delay-2 { animation-delay: 0.2s; }
        .stagger-delay-3 { animation-delay: 0.3s; }
        .stagger-delay-4 { animation-delay: 0.4s; }
        .stagger-delay-5 { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <div class="logo-section">
                <img src="img/logo.JPG" alt="Logo" class="logo">
                <div class="system-title">
                   <h1>Debre Tabor University Student Union Voting System</h1>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-menu fade-in">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="contacts.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
               <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->
                <li><a href="advert.php"><i class="fas fa-bullhorn"></i> Advert</a></li>
                <li class="active"><a href="dev.php"><i class="fas fa-code"></i> Developer</a></li>
                <li><a href="candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote</a></li>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content fade-in">
            <!-- Sidebar -->
            <aside class="sidebar">
                <img src="deve/dt.PNG" alt="Developer" class="developer-photo">
                <div class="election-date">
                    <h3>Election Date of 2017</h3>
                    <?php
                    $result = mysqli_query($conn, "SELECT * FROM election_date");
                    while($row = mysqli_fetch_array($result)) {
                        $date = $row['date'];
                    ?>
                    <div class="date-display">
                        <?php echo htmlspecialchars($date); ?>
                    </div>
                    <?php } ?>
                </div>
            </aside>

            <!-- Content Area -->
            <section class="content-area">
                <h1 class="page-title">ONLINE VOTING SYSTEM DEVELOPERS</h1>
                
                <!-- Developers Grid (3 columns for 5 developers) -->
                <div class="developers-grid">
                    <!-- Developer 1 -->
                    <div class="developer-card fade-in stagger-delay-1">
                        <img src="deve/c.JPG" alt="Desalegn Tibebu" class="developer-photo-large">
                        <h2 class="developer-name">Desalegn Tibebu</h2>
                        <p class="developer-title"> Backend Developer and Frontend Developer </p>
                        <div class="developer-year">
                            <i class="fas fa-graduation-cap"></i>  Information Technology 4<sup>th</sup> Year  
                        </div>
                    </div>

                    <!-- Developer 2 -->
                    <div class="developer-card fade-in stagger-delay-2">
                        <img src="deve/jo.JPG" alt="Yohannes Aregay" class="developer-photo-large">
                        <h2 class="developer-name">Yohannes Aregay</h2>
                        <p class="developer-title">Frontend Developer & UI/UX Designer</p>
                        <div class="developer-year">
                            <i class="fas fa-graduation-cap"></i> Information Technology 4<sup>th</sup> Year
                        </div>
                    </div>

                    <!-- Developer 3 -->
                    <div class="developer-card fade-in stagger-delay-3">
                        <img src="deve/ne.JPG" alt="Nejat Abdu" class="developer-photo-large">
                        <h2 class="developer-name">Nejat Abdu</h2>
                        <p class="developer-title">Backend Developer & Database Specialist</p>
                        <div class="developer-year">
                            <i class="fas fa-graduation-cap"></i> Information Technology 4<sup>th</sup> Year
                        </div>
                    </div>

                    <!-- Developer 4 -->
                    <div class="developer-card fade-in stagger-delay-4">
                        <img src="deve/ba.JPG" alt="Bamlak Tizazu" class="developer-photo-large">
                        <h2 class="developer-name">Bamlak Tizazu</h2>
                        <p class="developer-title">Security Analyst & Quality Assurance</p>
                        <div class="developer-year">
                            <i class="fas fa-graduation-cap"></i> Information Technology 4<sup>th</sup> Year
                        </div>
                    </div>

                    <!-- Developer 5 -->
                    <div class="developer-card fade-in stagger-delay-5">
                        <img src="deve/bi.JPG" alt="Biftu Tinti" class="developer-photo-large">
                        <h2 class="developer-name">Biftu Tinti</h2>
                        <p class="developer-title">System Architect & DevOps Engineer</p>
                        <div class="developer-year">
                            <i class="fas fa-graduation-cap"></i> Information Technology 4<sup>th</sup> Year
                        </div>
                    </div>
                </div>

                <!-- Team Information Section (Without Statistics) -->
                <div class="team-info fade-in">
                    <h2 class="team-title">Development Team Overview</h2>
                    <p class="team-description">
                        Our dedicated team of final-year Information Technology students from Debre Tabor University 
                        has collaborated to develop this secure, transparent, and user-friendly online voting system. 
                        Each member brings specialized expertise to ensure the system meets the highest standards 
                        of security, usability, and reliability for student union elections.
                    </p>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="footer fade-in">
            <p class="copyright">
                <i class="far fa-copyright"></i> Copyright <?php echo date('Y'); ?> Debre Tabor University Student Union. All rights reserved.
            </p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="themes/4/js-image-slider.js"></script>
    <script>
        // Add active state to current page in navigation
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-menu a');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.parentElement.classList.add('active');
                }
            });

            // Add hover effect to all developer cards
            const devCards = document.querySelectorAll('.developer-card');
            devCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Staggered animation for cards
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.developer-card').forEach(card => {
                observer.observe(card);
            });
        });
    </script>
    <?php mysqli_close($conn); ?>
</body>
</html>