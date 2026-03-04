<?php 
require_once __DIR__ . '/includes/db.php';
include 'includes/header.php'; 
?>

<link rel="stylesheet" href="css/contact-us.css">

<main class="contact-page">

    <!-- HERO SECTION -->
    <section class="contact-hero">
        <div class="container">
            <h1>Contact Us</h1>
            <p>
                Have questions about a lost item or need assistance? 
                We’re here to help you.
            </p>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section class="contact-section">
        <div class="container contact-grid">

            <!-- CONTACT INFO -->
            <div class="contact-card">
                <h2>Get in Touch</h2>
                <p>If you need support regarding a lost or found item, you may reach us through the following:</p>

                <div class="contact-info">
                    <p><strong>Email:</strong> support@foundit.com</p>
                    <p><strong>Phone:</strong> +63 912 345 6789</p>
                    <p><strong>Office Hours:</strong> Monday – Friday, 8:00 AM – 5:00 PM</p>
                    <p><strong>Location:</strong> Campus Administration Office</p>
                </div>
            </div>

            <!-- CONTACT FORM -->
            <div class="contact-card">
                <h2>Send Us a Message</h2>

                <form action="#" method="POST" class="contact-form">
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Send Message</button>

                </form>
            </div>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>