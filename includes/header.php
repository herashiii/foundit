<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : '';

$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoundiT - Campus Lost & Found</title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="icon" type="image/x-icon" href="../favicon2.ico">
    <link rel="shortcut icon" href="../favicon2.ico">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../css/base.css">

    <?php if(file_exists(__DIR__ . "/../css/" . $current_page . ".css")): ?>
        <link rel="stylesheet" href="../css/<?= htmlspecialchars($current_page) ?>.css">
    <?php endif; ?>

    <style>
        /* 1. Desktop Consistency (Ensures nothing moves) */
        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative; /* Anchor for mobile positioning */
        }

        #menu-toggle, .menu-icon {
            display: none; /* Completely hidden on desktop */
        }

        .nav-actions .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            padding: 8px 20px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-actions .btn-primary {
            background-color: #9B2C2C;
            color: #FFFFFF;
            border: 2px solid #9B2C2C;
        }
        
        .nav-actions .btn-primary:hover {
            background-color: #742A2A;
            transform: translateY(-1px);
        }
        
        .nav-actions .btn-secondary {
            background-color: #FFFFFF;
            color: #9B2C2C;
            border: 2px solid #9B2C2C;
        }
        
        .nav-actions .btn-secondary:hover {
            background-color: #FFF5F5;
            transform: translateY(-1px);
        }

        .mobile-only-links {
            display: none;
        }

        /* 2. Mobile Responsiveness (Triggers at 992px) */
        @media (max-width: 992px) {
            .menu-icon {
                /* Changed to flex to properly center the hamburger bars against the logo */
                display: flex;
                flex-direction: column;
                justify-content: center;
                order: 3; 
                cursor: pointer;
                padding: 5px 0 5px 10px; /* Adjusted padding to prevent misalignment */
                margin: 0;
            }

            .menu-icon span {
                display: block;
                width: 25px;
                height: 3px;
                background: var(--primary);
                margin: 3px 0; /* Tightened the gap for a cleaner look */
                border-radius: 2px;
                transition: 0.3s;
            }

            .nav-links {
                display: none; 
                flex-direction: column;
                position: absolute;
                top: var(--header-height);
                left: 0;
                width: 100%;
                background: #fff;
                padding: 20px;
                box-shadow: var(--shadow-md);
                z-index: 1001;
                gap: 15px;
            }

            #menu-toggle:checked ~ .nav-links {
                display: flex;
            }

            .nav-actions {
                display: none; 
            }

            .mobile-only-links {
                /* Force mobile links to stack nicely */
                display: flex;
                flex-direction: column;
                gap: 12px;
                border-top: 1px solid var(--border);
                margin-top: 10px;
                padding-top: 15px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar" aria-label="Main Navigation">
        <div class="container nav-container">
            <a href="../Pages/index.php" class="logo" aria-label="FoundiT Home">
                <img src="../favicon.png" alt="FoundiT Logo" class="logo-img" style="width: 24px; height: 24px; margin-right: 8px;">
                <div>Found<span>iT</span></div>
            </a>

            <input type="checkbox" id="menu-toggle" style="display: none;">
            
            <div class="nav-links">
                <a href="../Pages/index.php" class="nav-link <?= $current_page == 'index' ? 'active' : '' ?>">Home</a>
                <a href="../Pages/site-map.php" class="nav-link <?= $current_page == 'site-map' ? 'active' : '' ?>">Site Map</a>
                <a href="../Pages/find-my-item.php" class="nav-link <?= $current_page == 'find-my-item' ? 'active' : '' ?>">Find My Item</a>
                <a href="../Pages/faq.php" class="nav-link <?= $current_page == 'faq' || $current_page == 'faqs' ? 'active' : '' ?>">FAQs</a>
                <a href="../Pages/aboutus.php" class="nav-link <?= $current_page == 'aboutus' ? 'active' : '' ?>">About Us</a>
                
                <div class="mobile-only-links">
                    <?php if ($isLoggedIn): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="../Pages/admindash.php" class="nav-link">Admin Dashboard</a>
                            <a href="#" onclick="confirmLogoutMobile(event)" class="nav-link">Log Out</a>
                        <?php else: ?>
                            <a href="../Pages/dashboard.php" class="nav-link">User Dashboard</a>
                            <a href="#" onclick="confirmLogoutMobile(event)" class="nav-link">Log Out</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="../Login/login.php" class="nav-link">Log In</a>
                        <a href="../Pages/turn-in-item.php" class="nav-link" style="color: var(--primary); font-weight: 700;">Turn In Item</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="../Pages/admindash.php" class="btn btn-primary">Admin Dashboard</a>
                        <button onclick="confirmLogout()" class="btn btn-secondary" style="border: 2px solid #9B2C2C; background: white; color: #9B2C2C; cursor: pointer;">Log Out</button>
                    <?php else: ?>
                        <a href="../Pages/dashboard.php" class="btn btn-secondary">User Dashboard</a>
                        <button onclick="confirmLogout()" class="btn btn-primary" style="background: #9B2C2C; color: white; border: 2px solid #9B2C2C; cursor: pointer;">Log Out</button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../Login/login.php" class="btn btn-secondary">Log In</a>
                    <a href="../Pages/turn-in-item.php" class="btn btn-primary">Turn In Item</a>
                <?php endif; ?>
            </div>

            <label for="menu-toggle" class="menu-icon">
                <span></span>
                <span></span>
                <span></span>
            </label>
        </div>
    </nav>

    <!-- Logout Confirmation Script -->
    <script>
        // Accessible logout confirmation for desktop
        function confirmLogout() {
            if (confirm('Are you sure you want to sign out? This will end your current session.')) {
                window.location.href = '../Pages/logout.php';
            }
        }

        // Accessible logout confirmation for mobile
        function confirmLogoutMobile(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to sign out? This will end your current session.')) {
                window.location.href = '../Pages/logout.php';
            }
        }

        // Add keyboard support for logout buttons
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtns = document.querySelectorAll('.nav-actions button[onclick="confirmLogout()"]');
            logoutBtns.forEach(btn => {
                btn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        confirmLogout();
                    }
                });
                
                // Add proper ARIA attributes for accessibility
                btn.setAttribute('aria-label', 'Log out of your account');
                btn.setAttribute('role', 'button');
                btn.setAttribute('tabindex', '0');
            });
        });
    </script>