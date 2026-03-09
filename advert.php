<?php
include("connection.php");
session_start();

// Enhanced connection check
if (!isset($conn) || !$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Security: Set content type and encoding headers
header('Content-Type: text/html; charset=UTF-8');

// Initialize variables
$message = '';
$current_role = $_SESSION['role'] ?? null;
$is_system_admin = ($current_role === 'system_admin');

// Handle POST request for advert posting
if ($is_system_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'post_advert') {
        // Sanitize and validate inputs
        $title = trim(htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8'));
        $content = trim(htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8'));
        $posted_by = trim(htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'));
        $date = date("Y-m-d H:i:s");

        // Validation
        if (empty($title) || empty($content)) {
            $message = '<div class="alert alert-error">Error: Title and content cannot be empty.</div>';
        } elseif (strlen($title) > 200) {
            $message = '<div class="alert alert-error">Error: Title is too long (max 200 characters).</div>';
        } else {
            // Prepare and execute statement with error handling
            $stmt = $conn->prepare("INSERT INTO event (title, content, posted_by, date) VALUES (?, ?, ?, ?)");
            
            if ($stmt === false) {
                error_log("Database prepare error: " . $conn->error);
                $message = '<div class="alert alert-error">Database error. Please try again later.</div>';
            } else {
                $stmt->bind_param("ssss", $title, $content, $posted_by, $date);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Advert posted successfully!</div>';
                    // Redirect to prevent form resubmission
                    header("Location: advert.php?success=1");
                    exit();
                } else {
                    error_log("Advert insert error: " . $stmt->error);
                    $message = '<div class="alert alert-error">Failed to post advert. Please try again.</div>';
                }
                $stmt->close();
            }
        }
    }
}

// Display success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = '<div class="alert alert-success">Advert posted successfully!</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System - Debre Tabor University</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    
    <!-- Modern CSS with responsive design -->
    <style>
        :root {
            --primary-color: #1a2a6c;
            --secondary-color: #2F4F4F;
            --accent-color: #51a351;
            --light-blue: #f0f4ff;
            --light-gray: #f5f5f5;
            --border-color: #e1e1e1;
            --text-dark: #333333;
            --text-light: #666666;
            --white: #ffffff;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(to right, var(--primary-color), #2a3c8c);
            color: var(--white);
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
            gap: 30px;
            flex-wrap: wrap;
        }

        .logo {
            height: 80px;
            width: auto;
        }

        .university-logo {
            height: 120px;
            width: auto;
        }

        /* Navigation */
        .navbar {
            background: var(--secondary-color);
            padding: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            padding: 15px 20px;
            display: block;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-menu .active a {
            background: var(--accent-color);
            color: var(--white);
        }

        .nav-menu .logout-btn {
            background: var(--danger);
            margin-left: auto;
        }

        .nav-menu .logout-btn:hover {
            background: #c82333;
        }

        /* Main Content Layout */
        .main-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            padding: 30px 0;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar */
        .sidebar {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sidebar-image {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .election-date {
            background: var(--light-blue);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--accent-color);
        }

        .election-date h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .date-display {
            color: var(--danger);
            font-size: 1.2rem;
            font-weight: bold;
        }

        /* Main Content Area */
        .content-area {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--success);
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: var(--danger);
            border: 1px solid #f5c6cb;
        }

        /* Form Styles */
        .form-container {
            background: var(--light-blue);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .form-container h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn:hover {
            background: #0d1e5a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Announcements Section */
        .announcements-header {
            color: var(--primary-color);
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-blue);
            font-size: 1.5rem;
        }

        .announcement-card {
            background: var(--white);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .announcement-title {
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .announcement-content {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .announcement-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-light);
            font-size: 0.9rem;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .announcement-author {
            color: var(--primary-color);
            font-style: italic;
        }

        /* Footer */
        .footer {
            background: var(--secondary-color);
            color: var(--white);
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(to right, var(--light-blue), #e8efff);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .welcome-banner h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.6rem;
        }

        .welcome-banner p {
            color: var(--text-light);
            line-height: 1.7;
        }

        .welcome-banner a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }

        .welcome-banner a:hover {
            text-decoration: underline;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border-color);
            margin-bottom: 20px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-menu {
                width: 100%;
                justify-content: center;
            }
            
            .nav-menu .logout-btn {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
            }
            
            .nav-menu li {
                width: 100%;
            }
            
            .announcement-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="img/logo.JPG" alt="Debre Tabor University Logo" class="logo">
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
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="help.php">Help</a></li>
                <li><a href="contacts.php">Contact Us</a></li>
               <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->
                <li class="active"><a href="advert.php">Announcements</a></li>
                <li><a href="candidate.php">Candidates</a></li>
                <li><a href="vote.php">Vote Now</a></li>
                <li><a href="login.php">Login</a></li>
                <?php if ($is_system_admin): ?>
                    <li class="logout-btn"><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>)</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <img src="deve/dt.PNG" alt="Debre Tabor University" class="sidebar-image">
                
                <div class="election-date">
                    <h3>Upcoming Election Date</h3>
                    <?php
                    $result = mysqli_query($conn, "SELECT * FROM election_date LIMIT 1");
                    if ($row = mysqli_fetch_assoc($result)) {
                        echo '<div class="date-display">' . htmlspecialchars($row['date']) . '</div>';
                    } else {
                        echo '<div class="date-display">To be announced</div>';
                    }
                    ?>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="content-area">
                <!-- Display Messages -->
                <?php echo $message; ?>

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <h2>Debre Tabor University Student Union Voting System</h2>
                    <p>
                        Vote with ease! The Debre Tabor University Student Union Voting System is now online, 
                        making it simple and secure to elect your leaders. Register, vote, and view results from 
                        anywhere using our user-friendly platform. Join thousands of students in shaping our 
                        university's future. Visit <a href="vote.php">Vote Now</a> to cast your vote today! 
                        Every voice counts!
                    </p>
                </div>

                <!-- Advert Form for System Admin -->
                <?php if ($is_system_admin): ?>
                <div class="form-container">
                    <h3>📢 Post New Announcement</h3>
                    <form method="POST" action="advert.php">
                        <input type="hidden" name="action" value="post_advert">
                        
                        <div class="form-group">
                            <label for="title">Announcement Title</label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   class="form-control" 
                                   placeholder="Enter announcement title"
                                   maxlength="200"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Announcement Content</label>
                            <textarea id="content" 
                                      name="content" 
                                      class="form-control" 
                                      placeholder="Enter detailed announcement content"
                                      required></textarea>
                        </div>
                        
                        <button type="submit" class="btn">Publish Announcement</button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Announcements Section -->
                <h2 class="announcements-header">📢 Recent Announcements & Events</h2>
                
                <?php
                $result = mysqli_query($conn, "SELECT * FROM event ORDER BY date DESC");
                if (mysqli_num_rows($result) > 0): 
                    while($row = mysqli_fetch_assoc($result)):
                ?>
                    <article class="announcement-card">
                        <h3 class="announcement-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                        </div>
                        
                        <div class="announcement-meta">
                            <div class="announcement-date">
                                📅 <?php echo date('F j, Y, g:i a', strtotime($row['date'])); ?>
                            </div>
                            <div class="announcement-author">
                                👤 Posted by: <?php echo htmlspecialchars($row['posted_by']); ?>
                            </div>
                        </div>
                    </article>
                <?php 
                    endwhile;
                else: 
                ?>
                    <div class="empty-state">
                        <div>📢</div>
                        <h3>No Announcements Yet</h3>
                        <p>Check back later for updates and announcements.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Debre Tabor University Student Union Election Commission. All rights reserved.</p>
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