<?php 
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';  

$pdo = db();
$messageSent = false;
$messageError = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, message, status, created_at) 
                VALUES (?, ?, ?, 'unread', NOW())
            ");
            $stmt->execute([$name, $email, $message]);
            $messageSent = true;
            
            // Clear form data on success
            $_POST = [];
            
        } catch (PDOException $e) {
            $messageError = "Failed to send message. Please try again later.";
            // Log error for debugging
            error_log("Contact form error: " . $e->getMessage());
        }
    } else {
        $messageError = implode('<br>', $errors);
    }
}
?>

<link rel="stylesheet" href="css/contact-us.css">

<main class="contact-page">

    <!-- HERO SECTION -->
    <section class="contact-hero" style="color: white !important;">
        <div class="container">
            <h1 style="color: white !important; margin: 0 0 20px 0; font-size: 48px; font-weight: 700;">Contact Us</h1>
            <p style="color: white !important; font-size: 18px; max-width: 700px; margin: 0 auto; opacity: 0.95;">
                Have questions about a lost item or need assistance? 
                We're here to help you.
            </p>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section class="contact-section">
        <div class="container contact-grid">

            <!-- CONTACT INFO - Expanded -->
            <div class="contact-card info-card">
                <h2>Get in Touch</h2>
                <p class="info-intro">If you need support regarding a lost or found item, you may reach us through the following:</p>

                <div class="contact-info-expanded">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <h3>Email</h3>
                            <p><a href="mailto:support@foundit.com">support@foundit.com</a></p>
                            <p class="info-note">We typically respond within 24 hours</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="info-content">
                            <h3>Phone</h3>
                            <p><a href="tel:+639123456789">+63 912 345 6789</a></p>
                            <p class="info-note">Monday – Friday, 8:00 AM – 5:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h3>Location</h3>
                            <p>Campus Administration Office</p>
                            <p class="info-note">2nd Floor, Main Building</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <h3>Office Hours</h3>
                            <p>Monday – Friday</p>
                            <p class="info-note">8:00 AM – 5:00 PM (Closed on Weekends)</p>
                        </div>
                    </div>
                </div>
                
                <div class="info-additional">
                    <p><i class="fas fa-info-circle"></i> For urgent concerns, please visit our office during operating hours or call the hotline.</p>
                </div>
            </div>

            <!-- CONTACT FORM -->
            <div class="contact-card form-card">
                <h2>Send Us a Message</h2>
                
                <!-- Success Message -->
                <?php if ($messageSent): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724;">
                        <strong><i class="fas fa-check-circle"></i> Message Sent!</strong>
                        <p style="margin-top: 8px; margin-bottom: 0;">Thank you for contacting us. We'll get back to you as soon as possible.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Error Message -->
                <?php if ($messageError): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; color: #721c24;">
                        <strong><i class="fas fa-exclamation-circle"></i> Error!</strong>
                        <p style="margin-top: 8px; margin-bottom: 0;"><?= $messageError ?></p>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="contact-form">
                    
                    <div class="form-group">
                        <label for="name">Full Name <span style="color: #e74c3c;">*</span></label>
                        <input type="text" id="name" name="name" required 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                               placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span style="color: #e74c3c;">*</span></label>
                        <input type="email" id="email" name="email" required 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="your.email@example.com">
                    </div>

                    <div class="form-group">
                        <label for="message">Your Message <span style="color: #e74c3c;">*</span></label>
                        <textarea id="message" name="message" rows="6" required 
                                  placeholder="Please describe your concern or question in detail..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-primary submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>

                    <p class="response-note">
                        <i class="fas fa-clock"></i> We'll respond within 24-48 hours
                    </p>

                </form>
            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>