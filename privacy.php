<?php
session_start();
include("connection.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.12);
            --radius: 16px;
            --radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--dark), #374151);
            color: white;
            padding: 40px 0;
            text-align: center;
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
        
        .header-content {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .logo-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .logo-text p {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
        }
        
        /* Navigation */
        .nav-container {
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-list {
            display: flex;
            list-style: none;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .nav-item {
            margin: 0;
        }
        
        .nav-link {
            display: block;
            padding: 20px 25px;
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }
        
        .nav-link:hover {
            color: var(--primary);
        }
        
        .nav-link.active {
            color: var(--primary);
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25px;
            right: 25px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px 3px 0 0;
        }
        
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .privacy-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            padding: 50px;
            margin-bottom: 40px;
        }
        
        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .page-title h2 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .page-title i {
            font-size: 2.5rem;
            color: var(--primary);
        }
        
        /* Privacy Sections */
        .privacy-section {
            margin-bottom: 40px;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
        }
        
        .section-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .section-content {
            padding: 0 20px;
        }
        
        .section-content p {
            margin-bottom: 15px;
            color: var(--gray);
            line-height: 1.8;
        }
        
        .section-content ul {
            list-style: none;
            margin: 20px 0;
        }
        
        .section-content li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
            color: var(--gray);
        }
        
        .section-content li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success);
            font-weight: bold;
        }
        
        /* Data Types Grid */
        .data-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .data-type-card {
            background: var(--light);
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-sm);
            padding: 25px;
            transition: var(--transition);
        }
        
        .data-type-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
            border-color: var(--primary);
        }
        
        .data-type-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        .data-type-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .data-type-card p {
            font-size: 0.95rem;
            color: var(--gray);
            line-height: 1.6;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding: 20px 0;
            margin: 30px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 80px;
            margin-bottom: 40px;
        }
        
        .timeline-dot {
            position: absolute;
            left: 24px;
            top: 0;
            width: 16px;
            height: 16px;
            background: var(--primary);
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        
        .timeline-content {
            background: var(--light);
            padding: 20px;
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
        }
        
        .timeline-content h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .timeline-content p {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        /* Contact Card */
        .contact-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--radius);
            padding: 40px;
            color: white;
            text-align: center;
            margin-top: 50px;
        }
        
        .contact-card h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .contact-card p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 25px;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }
        
        .contact-item i {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0;
            text-align: center;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .footer-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-link:hover {
            color: white;
        }
        
        .copyright {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .logo {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-list {
                flex-direction: column;
            }
            
            .nav-link {
                padding: 15px;
                text-align: center;
            }
            
            .nav-link.active::after {
                left: 15px;
                right: 15px;
            }
            
            .privacy-container {
                padding: 30px 20px;
            }
            
            .page-title {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .data-types-grid {
                grid-template-columns: 1fr;
            }
            
            .timeline::before {
                left: 20px;
            }
            
            .timeline-item {
                padding-left: 60px;
            }
            
            .timeline-dot {
                left: 14px;
            }
            
            .contact-info {
                flex-direction: column;
                gap: 15px;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease forwards;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <div class="logo-text">
                    <h1>Online Voting System</h1>
                    <p>Secure • Transparent • Democratic</p>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Navigation -->
    <nav class="nav-container">
        <div class="nav">
            <ul class="nav-list">
                <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> About</a></li>
                <li class="nav-item"><a href="help.php" class="nav-link"><i class="fas fa-question-circle"></i> Help</a></li>
                <li class="nav-item"><a href="contacts.php" class="nav-link"><i class="fas fa-address-book"></i> Contact</a></li>
                <li class="nav-item"><a href="privacy.php" class="nav-link active"><i class="fas fa-shield-alt"></i> Privacy</a></li>
                <li class="nav-item"><a href="terms.php" class="nav-link"><i class="fas fa-file-contract"></i> Terms</a></li>
            </ul>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="privacy-container animate-fade-in">
            <div class="page-title">
                <i class="fas fa-user-shield"></i>
                <h2>Privacy Policy</h2>
            </div>
            
            <p style="font-size: 1.1rem; color: var(--gray); margin-bottom: 30px;">
                Last Updated: <?php echo date('F d, Y'); ?>
            </p>
            
            <!-- Introduction -->
            <section class="privacy-section animate-fade-in delay-100">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3 class="section-title">Introduction</h3>
                </div>
                <div class="section-content">
                    <p>Welcome to the Online Voting System Privacy Policy. We are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our voting platform.</p>
                    <p>By using our services, you consent to the data practices described in this policy. If you do not agree with the terms of this Privacy Policy, please do not access or use our voting system.</p>
                </div>
            </section>
            
            <!-- Information We Collect -->
            <section class="privacy-section animate-fade-in delay-200">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="section-title">Information We Collect</h3>
                </div>
                <div class="section-content">
                    <p>We collect several types of information for various purposes to provide and improve our voting services to you:</p>
                    
                    <div class="data-types-grid">
                        <div class="data-type-card">
                            <div class="data-type-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4>Personal Information</h4>
                            <p>Full name, email address, phone number, identification numbers, and date of birth for voter verification.</p>
                        </div>
                        
                        <div class="data-type-card">
                            <div class="data-type-icon">
                                <i class="fas fa-fingerprint"></i>
                            </div>
                            <h4>Authentication Data</h4>
                            <p>Username, encrypted password, and security questions for account access and identity verification.</p>
                        </div>
                        
                        <div class="data-type-card">
                            <div class="data-type-icon">
                                <i class="fas fa-vote-yea"></i>
                            </div>
                            <h4>Voting Data</h4>
                            <p>Your vote selections, voting timestamps, and ballot information (stored anonymously for result tabulation).</p>
                        </div>
                        
                        <div class="data-type-card">
                            <div class="data-type-icon">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            <h4>Technical Data</h4>
                            <p>IP address, browser type, device information, and system logs for security and performance monitoring.</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- How We Use Your Information -->
            <section class="privacy-section animate-fade-in delay-300">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="section-title">How We Use Your Information</h3>
                </div>
                <div class="section-content">
                    <p>We use the collected information for various purposes:</p>
                    <ul>
                        <li>To verify your identity and eligibility to vote</li>
                        <li>To create and manage your voting account</li>
                        <li>To process and record your vote securely</li>
                        <li>To prevent fraudulent activities and ensure election integrity</li>
                        <li>To generate anonymous statistical reports</li>
                        <li>To comply with legal obligations and election regulations</li>
                        <li>To provide technical support and system maintenance</li>
                        <li>To improve our voting platform and user experience</li>
                    </ul>
                </div>
            </section>
            
            <!-- Data Protection -->
            <section class="privacy-section animate-fade-in delay-400">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="section-title">Data Protection & Security</h3>
                </div>
                <div class="section-content">
                    <p>We implement robust security measures to protect your personal information:</p>
                    
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Encryption</h4>
                                <p>All sensitive data is encrypted using industry-standard AES-256 encryption during transmission and storage.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Access Control</h4>
                                <p>Strict access controls and authentication mechanisms ensure only authorized personnel can access voter data.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Audit Trails</h4>
                                <p>Comprehensive audit logs track all system activities for accountability and forensic analysis.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Regular Security Audits</h4>
                                <p>Third-party security audits and penetration testing are conducted regularly to identify and address vulnerabilities.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Data Retention -->
            <section class="privacy-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="section-title">Data Retention</h3>
                </div>
                <div class="section-content">
                    <p>We retain personal information only for as long as necessary to fulfill the purposes outlined in this Privacy Policy:</p>
                    <ul>
                        <li><strong>Voter Registration Data:</strong> Retained for 7 years after account deactivation as required by election laws</li>
                        <li><strong>Voting Records:</strong> Anonymized voting data retained permanently for election verification</li>
                        <li><strong>System Logs:</strong> Retained for 2 years for security monitoring and incident investigation</li>
                        <li><strong>Inactive Accounts:</strong> Deleted after 3 years of inactivity with prior notification</li>
                    </ul>
                </div>
            </section>
            
            <!-- Your Rights -->
            <section class="privacy-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3 class="section-title">Your Privacy Rights</h3>
                </div>
                <div class="section-content">
                    <p>As a user of our voting system, you have the following rights regarding your personal data:</p>
                    <ul>
                        <li><strong>Right to Access:</strong> Request a copy of your personal data we hold</li>
                        <li><strong>Right to Rectification:</strong> Request correction of inaccurate or incomplete data</li>
                        <li><strong>Right to Erasure:</strong> Request deletion of your personal data under certain conditions</li>
                        <li><strong>Right to Restrict Processing:</strong> Request restriction of how we use your data</li>
                        <li><strong>Right to Data Portability:</strong> Request transfer of your data to another organization</li>
                        <li><strong>Right to Object:</strong> Object to certain types of data processing</li>
                    </ul>
                </div>
            </section>
            
            <!-- Contact Information -->
            <div class="contact-card animate-fade-in">
                <h3><i class="fas fa-headset"></i> Questions or Concerns?</h3>
                <p>If you have any questions about this Privacy Policy or our data practices, please contact our Data Protection Officer:</p>
                
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>privacy@onlinevoting.et</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+251 11 123 4567</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-building"></i>
                        <span>Electoral Commission, Addis Ababa</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div style="margin-bottom: 20px;">
                <i class="fas fa-vote-yea" style="font-size: 2rem; color: rgba(255,255,255,0.8); margin-bottom: 15px;"></i>
                <p style="font-size: 1.1rem; opacity: 0.9;">Secure Online Voting System</p>
            </div>
            
            <div class="footer-links">
                <a href="index.php" class="footer-link">Home</a>
                <a href="about.php" class="footer-link">About Us</a>
                <a href="help.php" class="footer-link">Help Center</a>
                <a href="contacts.php" class="footer-link">Contact</a>
                <a href="privacy.php" class="footer-link">Privacy Policy</a>
                <a href="terms.php" class="footer-link">Terms of Service</a>
            </div>
            
            <div class="copyright">
                <p>© <?php echo date("Y"); ?> Ethiopian Electoral Commission. All rights reserved.</p>
                <p style="margin-top: 10px; font-size: 0.85rem;">This voting system is certified compliant with national election regulations.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add animation to elements when they come into view
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        }, observerOptions);
        
        // Observe all privacy sections
        document.querySelectorAll('.privacy-section').forEach(section => {
            observer.observe(section);
        });
        
        // Update current year in footer
        document.addEventListener('DOMContentLoaded', function() {
            const yearSpan = document.getElementById('currentYear');
            if (yearSpan) {
                yearSpan.textContent = new Date().getFullYear();
            }
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>