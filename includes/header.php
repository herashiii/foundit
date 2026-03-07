<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : '';

$current_page = basename($_SERVER['PHP_SELF'], ".php");
$page_css_path = __DIR__ . "/../css/" . $current_page . ".css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoundiT - Campus Lost & Found</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon2.ico">
    <link rel="shortcut icon" href="../favicon2.ico">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Global Styles -->
    <link rel="stylesheet" href="../css/base.css">

    <!-- Page Specific Styles -->
    <?php if(file_exists(__DIR__ . "/../css/" . $current_page . ".css")): ?>
        <link rel="stylesheet" href="../css/<?= htmlspecialchars($current_page) ?>.css">
    <?php endif; ?>

    <!-- Ensure Header Button Styles follow strict Project Rules -->
    <style>
        .nav-actions .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-actions .btn-primary {
            background-color: #9B2C2C; /* Silliman Red */
            color: #FFFFFF;
            border: 2px solid #9B2C2C;
        }
        
        .nav-actions .btn-primary:hover {
            background-color: #742A2A; /* Deep Maroon */
            border-color: #742A2A;
            transform: translateY(-1px);
        }
        
        .nav-actions .btn-secondary {
            background-color: #FFFFFF; /* Surface White */
            color: #9B2C2C; /* Silliman Red */
            border: 2px solid #9B2C2C;
        }
        
        .nav-actions .btn-secondary:hover {
            background-color: #FFF5F5; /* Soft Rose Wash */
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <nav class="navbar" aria-label="Main Navigation">
        <div class="container nav-container">
            <!-- Logo -->
            <a href="../Pages/index.php" class="logo" aria-label="FoundiT Home">
                <img src="../favicon.png" alt="FoundiT Logo" class="logo-img" style="width: 24px; height: 24px; object-fit: contain; margin-right: 8px;">
                <div>Found<span>iT</span></div>
            </a>

            <div class="nav-links">
                <a href="../Pages/index.php" class="nav-link <?= $current_page == 'index' ? 'active' : '' ?>">Home</a>
                <a href="../Pages/site-map.php" class="nav-link <?= $current_page == 'site-map' ? 'active' : '' ?>">Site Map</a>
                <a href="../Pages/find-my-item.php" class="nav-link <?= $current_page == 'find-my-item' ? 'active' : '' ?>">Find My Item</a>
                <a href="../Pages/faq.php" class="nav-link <?= $current_page == 'faq' || $current_page == 'faqs' ? 'active' : '' ?>">FAQs</a>
                <a href="../Pages/aboutus.php" class="nav-link <?= $current_page == 'aboutus' ? 'active' : '' ?>">About Us</a>
            </div>

            <!-- Dynamic Header Actions based on User State -->
            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <!-- 1. ADMIN LOGIC: Admins manage items, they don't turn them in -->
                        <a href="../Pages/admindash.php" class="btn btn-primary" style="padding: 8px 20px;">Admin Dashboard</a>
                        <a href="../Pages/logout.php" class="btn btn-secondary" style="padding: 8px 20px;">Log Out</a>
                        
                    <?php else: ?>
                        <!-- 2. STUDENT LOGIC: Students track their portal and turn in items -->
                        <a href="../Pages/dashboard.php" class="btn btn-secondary" style="padding: 8px 20px;">User Dashboard</a>
                        <a href="../Pages/logout.php" class="btn btn-primary" style="padding: 8px 20px;">Log Out</a>
                    <?php endif; ?>
                    
                <?php else: ?>
                    
                    <!-- 3. GUEST LOGIC: Default view -->
                    <a href="../Login/login.php" class="btn btn-secondary" style="padding: 8px 20px;">Log In</a>
                    <a href="../Pages/turn-in-item.php" class="btn btn-primary" style="padding: 8px 20px;">Turn In Item</a>
                    
                <?php endif; ?>
            </div>
            
        </div>
    </nav>