<?php
    include("connection.php");
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ethiopian Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2F4F4F;
            --secondary: #006400;
            --accent: #DAA520;
            --light: #F8F9FA;
            --dark: #343A40;
            --gray: #6C757D;
            --danger: #DC3545;
            --success: #28A745;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: var(--border-radius);
            padding: 15px 30px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-container img {
            height: 70px;
            border-radius: var(--border-radius);
            border: 3px solid var(--accent);
        }

        .system-title {
            color: white;
            text-align: center;
            flex-grow: 1;
        }

        .system-title h1 {
            font-size: 2.2rem;
            margin-bottom: 5px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        .system-title p {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Navigation Menu */
        .nav-container {
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-menu li {
            flex: 1;
            min-width: 120px;
            text-align: center;
        }

        .nav-menu a {
            display: block;
            padding: 18px 15px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }

        .nav-menu a:hover {
            background: var(--light);
            color: var(--secondary);
            border-bottom: 3px solid var(--accent);
        }

        .nav-menu a i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        /* Main Content Layout */
        .main-content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar Styles */
        .sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
        }

        .profile-card {
            text-align: center;
            margin-bottom: 25px;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--accent);
            margin-bottom: 15px;
        }

        .date-card {
            background: linear-gradient(135deg, #2F4F4F, #006400);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            transition: var(--transition);
        }

        .date-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .date-card h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .date-card .date-range {
            font-size: 1.4rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .date-card .date-single {
            font-size: 1.6rem;
            font-weight: bold;
            margin: 10px 0;
            color: var(--accent);
        }

        /* Content Area */
        .content-area {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
        }

        .section-title {
            color: var(--secondary);
            font-size: 2rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--accent);
            text-align: center;
        }

        .rules-section {
            margin-bottom: 30px;
        }

        .rules-title {
            color: var(--primary);
            font-size: 1.5rem;
            margin: 25px 0 15px;
            padding-left: 15px;
            border-left: 4px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rules-list {
            list-style: none;
            padding-left: 20px;
        }

        .rules-list li {
            padding: 12px 15px;
            margin-bottom: 10px;
            background: var(--light);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--secondary);
            transition: var(--transition);
        }

        .rules-list li:hover {
            transform: translateX(5px);
            background: #e8f5e9;
        }

        .rules-list li:before {
            content: "✓";
            color: var(--success);
            font-weight: bold;
            margin-right: 10px;
        }

        /* Footer */
        .footer {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 25px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--box-shadow);
            margin-top: 25px;
        }

        .footer p {
            margin-bottom: 10px;
        }

        .footer a {
            color: var(--accent);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer a:hover {
            color: white;
            text-decoration: underline;
        }

        .copyright {
            opacity: 0.8;
            font-size: 0.9rem;
            margin-top: 15px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .logo-container {
                justify-content: center;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .nav-menu li {
                min-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 10px;
            }
            
            .main-content {
                gap: 15px;
            }
            
            .content-area,
            .sidebar {
                padding: 20px;
            }
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }

        .badge-new {
            background: var(--accent);
            color: var(--dark);
        }

        .badge-important {
            background: var(--danger);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo-container">
                <img src="img/logo.JPG" alt="Ethiopian Flag Logo">
               <!-- <img src="img/log.png" alt="Voting System Logo">-->
            </div>
            <div class="system-title">
                <h1>Debre Tabor University
Student Union   Voting System</h1>
                <p>Secure • Transparent • Democratic</p>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-container">
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="candidate.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote <span class="badge badge-important">Live</span></a></li>
               <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="contacts.php"><i class="fas fa-envelope"></i> Contact</a></li>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="profile-card">
                    <img src="deve/dt.PNG" alt="System Profile" class="profile-img">
                    <h3>Online Voting Portal</h3>
                    <p>Hossana City Administration</p>
                </div>

                <!-- Voter Registration Date -->
                <?php
                $resultVoter = mysqli_query($conn, "SELECT * FROM voter_reg_date");
                while($row = mysqli_fetch_array($resultVoter)) {
                    $start = htmlspecialchars($row['start']);
                    $end = htmlspecialchars($row['end']);
                ?>
                <div class="date-card">
                    <h3><i class="fas fa-user-plus"></i> Voter Registration</h3>
                    <div class="date-range">
                        <?php echo $start; ?> <i class="fas fa-arrow-right"></i><br>
                        <?php echo $end; ?>
                    </div>
                    <p>Register to exercise your democratic right</p>
                </div>
                <?php } ?>

                <!-- Candidate Registration Date -->
                <?php
                $resultCand = mysqli_query($conn, "SELECT * FROM candidate_reg_date");
                while($row = mysqli_fetch_array($resultCand)) {
                    $start = htmlspecialchars($row['start']);
                    $end = htmlspecialchars($row['end']);
                ?>
                <div class="date-card">
                    <h3><i class="fas fa-user-tie"></i> Candidate Registration</h3>
                    <div class="date-range">
                        <?php echo $start; ?> <i class="fas fa-arrow-right"></i><br>
                        <?php echo $end; ?>
                    </div>
                    <p>Register to become a candidate</p>
                </div>
                <?php } ?>

                <!-- Election Date -->
                <?php
                $resultElec = mysqli_query($conn, "SELECT * FROM election_date");
                while($row = mysqli_fetch_array($resultElec)) {
                    $date = htmlspecialchars($row['date']);
                ?>
                <div class="date-card">
                    <h3><i class="fas fa-calendar-alt"></i> Election Date</h3>
                    <div class="date-single"><?php echo $date; ?></div>
                    <p>Cast your vote on this important day</p>
                </div>
                <?php } ?>
            </aside>

            <!-- Main Content Area -->
            <main class="content-area">
                <h1 class="section-title">System Rules & Guidelines</h1>
                
                <div class="rules-section">
                    <h2 class="rules-title"><i class="fas fa-users"></i> For Voters</h2>
                    <ol class="rules-list">
                        <li>You must have a minimum CGPA of 2.7 to be eligible to vote.</li>
                        <li>Must be an Ethiopian citizen with valid ID indicating residence in Hossana city.</li>
                        <li>Must meet all eligibility criteria as per election regulations.</li>
                        <li>Visit the election officer's office during registration days for verification.</li>
                        <li>Present your valid ID card to the election officer for registration.</li>
                        <li>Upon successful registration, you will receive election schedule details.</li>
                        <li>Cast your vote online within the specified time period only.</li>
                        <li>You may select only one candidate by checking the box next to their symbol.</li>
                    </ol>
                </div>

                <div class="rules-section">
                    <h2 class="rules-title"><i class="fas fa-user-tie"></i> For Candidates</h2>
                    <ol class="rules-list">
                        <li>Minimum age requirement: 22 years old.</li>
                        <li>Must be an Ethiopian citizen.</li>
                        <li>Must meet all eligibility requirements set by election commission.</li>
                        <li>Registration through the Election Office is mandatory.</li>
                        <li>Candidates may post campaign news and announcements through approved channels.</li>
                    </ol>
                </div>

                <div class="rules-section">
                    <h2 class="rules-title"><i class="fas fa-user-shield"></i> For Election Officers</h2>
                    <ol class="rules-list">
                        <li>Responsible for registering candidates and voters through the system.</li>
                        <li>Manage and communicate election schedules to registered participants.</li>
                        <li>Generate election reports and monitor system activities.</li>
                        <li>Review and address comments/feedback from candidates and voters.</li>
                        <li>Ensure election integrity and process transparency.</li>
                    </ol>
                </div>

                <div class="rules-section">
                    <h2 class="rules-title"><i class="fas fa-user-cog"></i> For System Administrators</h2>
                    <ol class="rules-list">
                        <li>Create and manage user accounts with appropriate access levels.</li>
                        <li>Configure and set election dates and registration periods.</li>
                        <li>Monitor system performance and ensure security protocols.</li>
                        <li>Generate comprehensive reports for all user categories.</li>
                        <li>Manage system backups and implement updates as needed.</li>
                    </ol>
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>Ethiopian Online Voting System &copy; 2017 EC</p>
            <p><a href="dev.php"><i class="fas fa-code"></i> Developer Team</a> | 
               <a href="contacts.php"><i class="fas fa-headset"></i> Support</a> | 
               <a href="#"><i class="fas fa-shield-alt"></i> Security</a> | 
               <a href="#"><i class="fas fa-file-contract"></i> Terms of Service</a></p>
            <div class="copyright">
                <p>This system is designed for Hossana City Administration elections.</p>
                <p>All rights reserved | Version 2.1.4</p>
            </div>
        </footer>
    </div>

    <?php mysqli_close($conn); ?>
</body>
</html>