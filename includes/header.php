<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    
    <link rel="icon" type="image/x-icon" href="../favicon2.ico">
    <link rel="shortcut icon" href="../favicon2.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/base.css">

    <?php if(file_exists(__DIR__ . "/../css/" . $current_page . ".css")): ?>
        <link rel="stylesheet" href="../css/<?= htmlspecialchars($current_page) ?>.css">
    <?php endif; ?>

    <style>
        /* Mobile Menu Toggle Logic */
        #menu-toggle { display: none; }

        .menu-icon {
            display: none;
            cursor: pointer;
            padding: 10px;
            user-select: none;
        }

        .menu-icon span {
            display: block;
            width: 25px;
            height: 3px;
            background: var(--primary);
            margin: 5px 0;
            transition: var(--transition);
        }

        /* Desktop Nav Actions Styling */
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
        
        .nav-actions .btn-primary { background-color: #9B2C2C; color: #FFFFFF; border: 2px solid #9B2C2C; }
        .nav-actions .btn-primary:hover { background-color: #742A2A; transform: translateY(-1px); }
        .nav-actions .btn-secondary { background-color: #FFFFFF; color: #9B2C2C; border: 2px solid #9B2C2C; }
        .nav-actions .btn-secondary:hover { background-color: #FFF5F5; transform: translateY(-1px); }

        /* Responsive Breakpoint (Matches base.css) */
        @media (max-width: 992px) {
            .menu-icon { display: block; }

            .nav-links {
                display: none; /* Controlled by checkbox hack in base.css */
                flex-direction: column;
                position: absolute;
                top: var(--header-height);
                left: 0;
                width: 100%;
                background: white;
                padding: 20px;
                box-shadow: var(--shadow-md);
                gap: 15px;
            }

            #menu-toggle:checked ~ .nav-links {
                display: flex;
            }

            .nav-actions { display: none; } /* Actions usually move inside mobile menu or remain hidden */
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

            <input type="checkbox" id="menu-toggle">
            <label for="menu-toggle" class="menu-icon">
                <span></span>
                <span></span>
                <span></span>
            </label>

            <div class="nav-links">
                <a href="../Pages/index.php" class="nav-link <?= $current_page == 'index' ? 'active' : '' ?>">Home</a>
                <a href="../Pages/site-map.php" class="nav-link <?= $current_page == 'site-map' ? 'active' : '' ?>">Site Map</a>
                <a href="../Pages/find-my-item.php" class="nav-link <?= $current_page == 'find-my-item' ? 'active' : '' ?>">Find My Item</a>
                <a href="../Pages/faq.php" class="nav-link <?= $current_page == 'faq' || $current_page == 'faqs' ? 'active' : '' ?>">FAQs</a>
                <a href="../Pages/aboutus.php" class="nav-link <?= $current_page == 'aboutus' ? 'active' : '' ?>">About Us</a>
                
                <div class="mobile-only-actions" style="margin-top: 15px; border-top: 1px solid var(--border); padding-top: 15px;">
                    <?php if ($isLoggedIn): ?>
                        <a href="../Pages/logout.php" class="nav-link">Log Out</a>
                    <?php else: ?>
                        <a href="../Login/login.php" class="nav-link">Log In</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="../Pages/admindash.php" class="btn btn-primary">Admin Dashboard</a>
                        <a href="../Pages/logout.php" class="btn btn-secondary">Log Out</a>
                    <?php else: ?>
                        <a href="../Pages/dashboard.php" class="btn btn-secondary">User Dashboard</a>
                        <a href="../Pages/logout.php" class="btn btn-primary">Log Out</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../Login/login.php" class="btn btn-secondary">Log In</a>
                    <a href="../Pages/turn-in-item.php" class="btn btn-primary">Turn In Item</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>