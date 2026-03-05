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
                    <h1 id="login-heading">Login to Your Account</h1>
                    <p>Use your Student ID and Birthdate to login.</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <?php 
                    // Determine error type for better messaging
                    $error = $_GET['error'];
                    $errorTitle = 'Login Failed';
                    $errorIcon = '⚠️';
                    
                    // Check if this is a redirect from turn-in-item
                    if (strpos($error, 'turn in an item') !== false) {
                        $errorTitle = 'Action Required';
                        $errorIcon = '🔒';
                    }
                    // Check for registration needed
                    elseif (strpos($error, 'register') !== false || strpos($error, 'not found') !== false) {
                        $errorTitle = 'Account Not Found';
                        $errorIcon = '❓';
                    }
                    // Check for wrong password
                    elseif (strpos($error, 'password') !== false || strpos($error, 'birthdate') !== false) {
                        $errorTitle = 'Incorrect Password';
                        $errorIcon = '🔑';
                    }
                    ?>
                    
                    <div class="error-message" role="alert" aria-labelledby="error-heading" data-error-type="<?= $errorTitle ?>">
                        <div class="error-icon" aria-hidden="true"><?= $errorIcon ?></div>
                        <div class="error-content">
                            <h3 id="error-heading" class="error-title"><?= $errorTitle ?></h3>
                            <p class="error-text"><?= h($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="success-message" role="status" aria-labelledby="success-heading">
                        <div class="success-icon" aria-hidden="true">✅</div>
                        <div class="success-content">
                            <h3 id="success-heading" class="success-title">Registration Successful!</h3>
                            <p class="success-text">Please login with your Student ID and birthdate.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logout'])): ?>
                    <div class="success-message" role="status" aria-labelledby="logout-heading">
                        <div class="success-icon" aria-hidden="true">👋</div>
                        <div class="success-content">
                            <h3 id="logout-heading" class="success-title">Logged Out Successfully</h3>
                            <p class="success-text">You have been logged out of your account.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="../Pages/process-login.php" method="POST" class="auth-form" aria-labelledby="login-heading">
                    <div class="form-group">
                        <label for="student_id">
                            Student ID <span class="required-indicator" aria-hidden="true">*</span>
                            <span class="sr-only">(required)</span>
                        </label>
                        <input 
                            type="text" 
                            name="student_id" 
                            id="student_id" 
                            placeholder="e.g., 22-1-00065" 
                            required
                            aria-required="true"
                            aria-describedby="student-id-hint"
                            class="<?= isset($_GET['error']) ? 'error' : '' ?>"
                        >
                        <div id="student-id-hint" class="input-hint">Enter your student ID (e.g., 22-1-00065)</div>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            Birthdate Password <span class="required-indicator" aria-hidden="true">*</span>
                            <span class="sr-only">(required)</span>
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            placeholder="e.g., 4172003" 
                            required
                            aria-required="true"
                            aria-describedby="password-hint password-format"
                            class="<?= isset($_GET['error']) ? 'error' : '' ?>"
                        >
                        <div id="password-hint" class="input-hint">Enter your birthdate as MMDDYYYY</div>
                        <div id="password-format" class="input-format">
                            <strong>Format:</strong> MMDDYYYY (no leading zeros, no separators)
                            <br>
                            <strong>Example:</strong> April 17, 2003 = 4172003
                        </div>
                    </div>

                    <div class="form-actions">
    <button type="submit" class="auth-btn" id="login-btn">
        <span id="btn-text">Sign In</span>
        <span id="btn-loading" style="display: none;">Logging in...</span>
    </button>
</div>

                    <div class="form-footer">
                        <p>
                            Don't have an account? 
                            <a href="../Pages/register.php" class="register-link">Register here</a>
                        </p>
                        <p class="help-text">
                            <a href="#" class="forgot-link">Forgot your password?</a>
                        </p>
                    </div>
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

// Make sure button starts in normal state
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.querySelector('.auth-btn');
    const btnText = btn?.querySelector('.btn-text');
    const btnLoading = btn?.querySelector('.btn-loading');
    
    if (btn && btnText && btnLoading) {
        btn.disabled = false;
        btnText.hidden = false;
        btnLoading.hidden = true;
    }
});

// Add loading state on form submit
document.querySelector('.auth-form')?.addEventListener('submit', function(e) {
    const btn = this.querySelector('.auth-btn');
    const btnText = btn?.querySelector('.btn-text');
    const btnLoading = btn?.querySelector('.btn-loading');
    
    // Only proceed if elements exist
    if (!btn || !btnText || !btnLoading) return;
    
    // Only show loading if form is valid
    if (this.checkValidity()) {
        btn.disabled = true;
        btnText.hidden = true;
        btnLoading.hidden = false;
        
        // Safety timeout - reset after 10 seconds if something goes wrong
        setTimeout(function() {
            if (btn.disabled) {
                btn.disabled = false;
                btnText.hidden = false;
                btnLoading.hidden = true;
            }
        }, 10000);
    }
});

// Reset button when page is shown (back/forward navigation)
window.addEventListener('pageshow', function() {
    const btn = document.querySelector('.auth-btn');
    const btnText = btn?.querySelector('.btn-text');
    const btnLoading = btn?.querySelector('.btn-loading');
    
    if (btn && btnText && btnLoading) {
        btn.disabled = false;
        btnText.hidden = false;
        btnLoading.hidden = true;
    }
});
</script>   

<style>
/* Enhanced Error Messages */
.error-message {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: #FEF2F2;
    border: 2px solid #DC2626;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
}

.error-icon {
    font-size: 28px;
    line-height: 1;
}

.error-content {
    flex: 1;
}

.error-title {
    color: #991B1B;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
}

.error-text {
    color: #7F1D1D;
    font-size: 15px;
    line-height: 1.5;
}

/* Success Messages */
.success-message {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: #F0FDF4;
    border: 2px solid #16A34A;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);
}

.success-icon {
    font-size: 28px;
    line-height: 1;
}

.success-content {
    flex: 1;
}

.success-title {
    color: #166534;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
}

.success-text {
    color: #14532D;
    font-size: 15px;
    line-height: 1.5;
}

/* Input error state */
input.error {
    border-color: #DC2626 !important;
    background-color: #FEF2F2 !important;
}

input.error:focus {
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.25) !important;
}

/* Input hints and formats */
.input-hint {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

.input-format {
    background: #F3F4F6;
    border-radius: 8px;
    padding: 10px 12px;
    margin-top: 8px;
    font-size: 13px;
    color: #4B5563;
    border-left: 3px solid #9B2C2C;
}

.input-format strong {
    color: #9B2C2C;
    font-weight: 600;
}

/* Required field indicator */
.required-indicator {
    color: #DC2626;
    font-weight: 700;
    margin-left: 2px;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* Form actions */
.form-actions {
    margin-top: 24px;
}

.auth-btn {
    width: 100%;
    padding: 14px;
    background: #9B2C2C;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    min-height: 48px;
}

.auth-btn:hover:not(:disabled) {
    background: #742A2A;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(155, 44, 44, 0.3);
}

.auth-btn:focus {
    outline: 3px solid #9B2C2C;
    outline-offset: 2px;
}

.auth-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-loading {
    display: inline-block;
    position: relative;
}

.btn-loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    margin-left: 8px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Form footer */
.form-footer {
    margin-top: 24px;
    text-align: center;
    padding-top: 16px;
    border-top: 1px solid #E5E7EB;
}

.form-footer p {
    margin: 8px 0;
}

.register-link {
    color: #9B2C2C;
    font-weight: 600;
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 4px;
}

.register-link:hover {
    background: #FEF2F2;
    text-decoration: underline;
}

.forgot-link {
    color: #666;
    font-size: 14px;
    text-decoration: none;
}

.forgot-link:hover {
    color: #9B2C2C;
    text-decoration: underline;
}

.help-text {
    font-size: 13px;
    color: #999;
}

/* Responsive */
@media (max-width: 768px) {
    .error-message,
    .success-message {
        padding: 16px;
    }
    
    .error-icon,
    .success-icon {
        font-size: 24px;
    }
    
    .error-title,
    .success-title {
        font-size: 16px;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .error-message {
        border: 3px solid #FF0000;
        background: #FFFFFF;
    }
    
    .success-message {
        border: 3px solid #00AA00;
        background: #FFFFFF;
    }
    
    input.error {
        border: 2px solid #FF0000;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .auth-btn:hover:not(:disabled) {
        transform: none;
    }
    
    .btn-loading::after {
        animation: none;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>