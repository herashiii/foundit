<?php 
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php'; 
?>

<link rel="stylesheet" href="css/aboutus.css">

<main class="about-page">

    <!-- HERO SECTION - Using inline style to guarantee white text -->
    <section class="about-hero" style="color: white !important;">
        <div class="container">
            <h1 style="color: white !important; margin: 0 0 20px 0; font-size: 48px; font-weight: 700;">About FoundiT</h1>
            <p style="color: white !important; font-size: 18px; max-width: 700px; margin: 0 auto; opacity: 0.95;">
                FoundIt! is a campus-based Lost and Found system designed to make
                reporting, searching, and claiming lost items simple, secure,
                and organized.
            </p>
        </div>
    </section>

    <!-- MISSION & VISION -->
    <section class="about-section">
        <div class="container about-grid">

            <div class="about-card">
                <h2>Our Mission</h2>
                <p>
                    To provide a centralized and reliable platform that helps students 
                    and staff reconnect with their lost belongings quickly and securely.
                </p>
            </div>

            <div class="about-card">
                <h2>Our Vision</h2>
                <p>
                    To create a responsible campus community where lost items are 
                    efficiently returned through a transparent and organized system.
                </p>
            </div>

        </div>
    </section>

    <!-- WHY WE BUILT THIS -->
    <section class="about-section light-bg">
        <div class="container about-content">
            <h2>Why We Created FoundIt!</h2>
            <p>
                Many lost items are reported through social media posts or word of mouth,
                which often leads to missed messages, delayed responses, and confusion.
                FoundIt! was developed to eliminate these issues by offering a structured
                digital system that keeps all reports in one accessible place.
            </p>
            <p>
                By combining user-friendly design with secure verification processes,
                we ensure that lost belongings are returned to their rightful owners
                safely and efficiently.
            </p>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>