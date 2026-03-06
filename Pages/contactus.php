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
                
                <!-- Success Message -->
                <?php if ($messageSent): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724;">
                        <strong>✅ Message Sent!</strong>
                        <p style="margin-top: 8px; margin-bottom: 0;">Thank you for contacting us. We'll get back to you as soon as possible.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Error Message -->
                <?php if ($messageError): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; color: #721c24;">
                        <strong>❌ Error!</strong>
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
                        <textarea id="message" name="message" rows="5" required 
                                  placeholder="Please describe your concern or question..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1.1rem;">
                        📨 Send Message
                    </button>

                    <p style="text-align: center; margin-top: 16px; font-size: 0.85rem; color: #666;">
                        We'll respond to your message within 24-48 hours.
                    </p>

                </form>
            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>