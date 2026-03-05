<?php 
session_start();

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: ../Pages/index.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

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
                    <p>Use your Student ID and Birthdate to login.</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="auth-alert error">
                        <p><?= h($_GET['error']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="auth-alert success">
                        <p>Registration successful! Please login with your Student ID and birthdate.</p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logout'])): ?>
                    <div class="auth-alert success">
                        <p>You have been successfully logged out.</p>
                    </div>
                <?php endif; ?>

                <form action="../Pages/process-login.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" name="student_id" id="student_id" placeholder="e.g., 22-1-00065" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Birthdate Password</label>
                        <input type="password" name="password" id="password" placeholder="e.g., 4172003" required>
                        <small style="color: #666; display: block; margin-top: 4px;">
                            Enter your birthdate as MMDDYYYY (no leading zeros, no separators)<br>
                            Example: April 17, 2003 = 4172003
                        </small>
                    </div>

                    <button type="submit" class="auth-btn">Sign In</button>

                    <p style="text-align: center; margin-top: 20px;">
                        Don't have an account? <a href="../Pages/register.php">Register here</a>
                    </p>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
// Only allow numbers in password field
document.getElementById('password').addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^0-9]/g, '');
    if (value.length > 8) value = value.slice(0, 8);
    e.target.value = value;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>