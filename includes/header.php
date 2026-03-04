<?php
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
            <a href="index.php" class="logo">
                <img src="../favicon.png" alt="Logo" class="logo-img">
                <div>Found<span>iT</span></div>
            </a>

            <!-- Desktop Menu -->
            <div class="nav-links">
                <a href="../Pages/index.php" class="nav-link">Home</a>
                <a href="find-my-item.php" class="nav-link">Find My Item</a>
                <a href="#" class="nav-link">How it Works</a>
                <a href="aboutus.php" class="nav-link">About Us</a>
                <a href="contactus.php" class="nav-link">Contact Us</a>
                <a href="#" class="nav-link">Site Map</a>
                <a href="#" class="nav-link">FAQs</a>
            </div>

            <div class="nav-actions">
                <a href="../Login/login.php" class="btn btn-secondary" style="padding: 8px 20px;">Log In</a>
                <a href="turn-in-item.php" class="btn btn-primary" style="padding: 8px 20px;">Turn In Item</a>
            </div>
        </div>
    </nav>