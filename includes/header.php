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
    <link rel="stylesheet" href="../css/<?php echo $current_page; ?>.css">
    <?php endif; ?>
</head>
<body>
    
    <!-- Navbar -->
<nav class="navbar">
    <div class="container nav-container">
        <!-- Updated Logo: Image + Text -->
        <a href="../Pages/index.php" class="logo">
            <img src="../favicon.png" alt="Logo" class="logo-img">
            <div>Found<span>iT</span></div>
        </a>

        <!-- Desktop Menu -->
        <div class="nav-links">
            <a href="../Pages/index.php" class="nav-link <?= $current_page == 'index' ? 'active' : '' ?>">
                Home
            </a>
            <a href="../Pages/find-my-item.php" class="nav-link <?= $current_page == 'find-my-item' ? 'active' : '' ?>">
                Find My Item
            </a>
            <a href="#" class="nav-link <?= $current_page == 'site-map' ? 'active' : '' ?>">
                Site Map
            </a>
            <a href="#" class="nav-link <?= $current_page == 'faqs' ? 'active' : '' ?>">
                FAQs
            </a>
            <a href="../Pages/aboutus.php" class="nav-link <?= $current_page == 'aboutus' ? 'active' : '' ?>">
                About Us
            </a>
        </div>

        <div class="nav-actions">
            <?php if ($isLoggedIn): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="../Pages/admindash.php" class="btn btn-primary" style="padding: 8px 20px;">
                        Admin Dashboard
                    </a>
                <?php else: ?>
                    <a href="../Pages/dashboard.php" class="btn btn-primary" style="padding: 8px 20px;">
                        User Dashboard
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="../Login/login.php" class="btn btn-secondary" style="padding: 8px 20px;">Log In</a>
            <?php endif; ?>
            <a href="../Pages/turn-in-item.php" class="btn btn-primary" style="padding: 8px 20px;">Turn In Item</a>
        </div>
     </div>
</nav>
    </nav>