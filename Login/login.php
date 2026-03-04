<?php 
require_once __DIR__ . '/includes/db.php';
include 'includes/header.php'; 

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<main class="auth-page">

    <section class="auth-hero">
        <div class="container auth-container">

            <div class="auth-card">

                <div class="auth-header">
                    <h2>Login to Your Account</h2>
                    <p>Access your lost and found activity.</p>
                </div>

                <form action="process-login.php" method="POST" class="auth-form">

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>

                    <button type="submit" class="auth-btn">
                        Sign In
                    </button>

                </form>

            </div>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
