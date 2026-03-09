<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service | <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Your Website'); ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #10b981;
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 40px rgba(99, 102, 241, 0.1);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.7;
            color: var(--dark);
            background: linear-gradient(135deg, #f0f4ff 0%, #fdf2ff 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out;
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            padding: 12px 24px;
            background: white;
            color: var(--primary);
            border: 2px solid var(--gray-light);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .back-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .back-btn i {
            font-size: 1.1rem;
        }

        /* Header */
        header {
            text-align: center;
            margin-bottom: 50px;
            padding: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: var(--radius);
            color: white;
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .last-updated {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .last-updated i {
            font-size: 0.9rem;
        }

        /* Terms Sections */
        .terms-section {
            background: white;
            padding: 40px;
            margin-bottom: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .terms-section:hover {
            transform: translateY(-5px);
            border-left-color: var(--primary);
            box-shadow: var(--shadow-hover);
        }

        h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        h2::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
        }

        h3 {
            color: var(--primary-dark);
            margin: 25px 0 15px 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        p {
            margin-bottom: 20px;
            color: var(--gray);
            font-size: 1.05rem;
        }

        /* Lists */
        ul {
            margin-left: 25px;
            margin-bottom: 25px;
        }

        li {
            margin-bottom: 12px;
            padding-left: 10px;
            position: relative;
            color: var(--gray);
        }

        li::before {
            content: '✓';
            position: absolute;
            left: -25px;
            color: var(--accent);
            font-weight: bold;
        }

        /* Contact Box */
        .contact-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 4px solid var(--primary);
            padding: 30px;
            border-radius: var(--radius);
            margin-top: 30px;
        }

        .contact-icons {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .contact-icon {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: white;
            border-radius: 12px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .contact-icon:hover {
            transform: translateY(-3px);
            background: var(--primary);
            color: white;
        }

        .contact-icon i {
            font-size: 1.2rem;
        }

        /* Footer */
        footer {
            text-align: center;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid var(--gray-light);
            color: var(--gray);
            font-size: 0.95rem;
        }

        .copyright {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            h1 {
                font-size: 2.2rem;
            }
            
            header {
                padding: 30px 20px;
            }
            
            .terms-section {
                padding: 25px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .contact-icons {
                flex-direction: column;
            }
            
            .back-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Highlight */
        .highlight {
            background: linear-gradient(120deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back to Home Button -->
        <a href="/" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Home</span>
        </a>

        <!-- Header -->
        <header>
            <h1>Terms of Service</h1>
            <p class="subtitle">Please read these terms carefully before using our services</p>
            <div class="last-updated">
                <i class="fas fa-calendar-alt"></i>
                <span>Last Updated: <?php echo date('F j, Y'); ?></span>
            </div>
        </header>
        
        <main>
            <!-- Section 1 -->
            <section class="terms-section">
                <h2>1. Agreement to Terms</h2>
                <p>By accessing and using <span class="highlight"><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'our website'); ?></span>, you accept and agree to be bound by these Terms of Service. If you disagree with any part of these terms, you may not access our services.</p>
                
                <h3>Acceptance Criteria</h3>
                <ul>
                    <li>You must have the legal capacity to enter into this agreement</li>
                    <li>You agree to comply with all applicable laws and regulations</li>
                    <li>You accept responsibility for your use of our services</li>
                </ul>
            </section>
            
            <!-- Section 2 -->
            <section class="terms-section">
                <h2>2. Intellectual Property Rights</h2>
                <p>All content, features, and functionality on this website are the exclusive property of <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'the website owner'); ?> and are protected by international copyright, trademark, and other intellectual property laws.</p>
                
                <h3>Permitted Use</h3>
                <ul>
                    <li>Personal, non-commercial use of content</li>
                    <li>Sharing with proper attribution</li>
                    <li>Educational purposes with citation</li>
                </ul>
            </section>
            
            <!-- Section 3 -->
            <section class="terms-section">
                <h2>3. User Responsibilities</h2>
                <p>As a user of our services, you agree to the following responsibilities:</p>
                
                <ul>
                    <li>You must be at least <span class="highlight">13 years old</span> to use this service</li>
                    <li>You are responsible for maintaining the security of your account credentials</li>
                    <li>You agree not to use the service for any illegal activities or purposes</li>
                    <li>You must not attempt to disrupt, disable, or interfere with service functionality</li>
                    <li>You will not impersonate any person or entity</li>
                    <li>You agree to provide accurate and complete information when required</li>
                </ul>
            </section>
            
            <!-- Section 4 -->
            <section class="terms-section">
                <h2>4. Limitation of Liability</h2>
                <p>To the fullest extent permitted by applicable law, we shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from:</p>
                
                <ul>
                    <li>Your use or inability to use the service</li>
                    <li>Any unauthorized access to or use of our servers</li>
                    <li>Any interruption or cessation of transmission to or from our service</li>
                    <li>Any bugs, viruses, or other harmful code that may be transmitted</li>
                    <li>Any errors or omissions in any content</li>
                </ul>
            </section>
            
            <!-- Section 5 -->
            <section class="terms-section">
                <h2>5. Changes to Terms</h2>
                <p>We reserve the right to modify or replace these terms at any time. When we make changes, we will update the "Last Updated" date at the top of this page.</p>
                
                <h3>Notification of Changes</h3>
                <ul>
                    <li>Major changes will be announced via email or website notification</li>
                    <li>Continued use of our services after changes constitutes acceptance</li>
                    <li>You are responsible for reviewing these terms periodically</li>
                </ul>
            </section>
            
            <!-- Section 6 -->
            <section class="terms-section">
                <h2>6. Contact Information</h2>
                <p>If you have any questions about these Terms of Service, please contact us using the information below:</p>
                
                <div class="contact-box">
                    <h3>Get in Touch</h3>
                    
                    <div class="contact-icons">
                        <a href="mailto:legal@<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'yourdomain.com'); ?>" class="contact-icon">
                            <i class="fas fa-envelope"></i>
                            <span>Email Legal Team</span>
                        </a>
                        
                        <a href="/contact" class="contact-icon">
                            <i class="fas fa-comments"></i>
                            <span>Contact Form</span>
                        </a>
                        
                        <a href="/support" class="contact-icon">
                            <i class="fas fa-headset"></i>
                            <span>Support Center</span>
                        </a>
                    </div>
                    
                    <p style="margin-top: 20px; font-size: 0.95rem; color: var(--gray);">
                        <i class="fas fa-clock"></i> Response Time: We aim to respond within 24-48 hours
                    </p>
                </div>
            </section>
        </main>
        
        <!-- Footer -->
        <footer>
            <p>By using our services, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</p>
            <div class="copyright">
                <i class="far fa-copyright"></i>
                <span><?php echo date('Y'); ?> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Your Website'); ?>. All rights reserved.</span>
            </div>
        </footer>
    </div>

    <!-- Optional JavaScript for smooth scroll -->
    <script>
        // Smooth scroll for anchor links
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

        // Back button functionality with history fallback
        document.querySelector('.back-btn').addEventListener('click', function(e) {
            if (window.history.length > 1) {
                e.preventDefault();
                window.history.back();
            }
        });

        // Add animation to sections on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all terms sections
        document.querySelectorAll('.terms-section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(section);
        });
    </script>
</body>
</html>