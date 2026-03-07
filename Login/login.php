<?php 
session_start();

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../Pages/admindash.php');
    } else {
        header('Location: ../Pages/index.php');
    }
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
                    <h1 id="login-heading">Login to FoundiT</h1>
                    <p>Choose your login type below</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <?php 
                    $error = $_GET['error'];
                    $errorTitle = 'Login Failed';
                    $errorIcon = '⚠️';
                    ?>
                    
                    <div class="error-message" role="alert">
                        <div class="error-icon" aria-hidden="true"><?= $errorIcon ?></div>
                        <div class="error-content">
                            <h3 class="error-title"><?= $errorTitle ?></h3>
                            <p class="error-text"><?= h($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="success-message" role="status">
                        <div class="success-icon" aria-hidden="true">✅</div>
                        <div class="success-content">
                            <h3 class="success-title">Registration Successful!</h3>
                            <p class="success-text">Please login with your Student ID and birthdate.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Tabs -->
                <div class="login-tabs" role="tablist" aria-label="Login type">
                    <button
                        class="tab-btn active"
                        id="tab-student-btn"
                        type="button"
                        onclick="switchTab('student')"
                        role="tab"
                        aria-selected="true"
                        aria-controls="student-login"
                    >
                        <span class="tab-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="tab-icon-svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 10L12 5 2 10l10 5 10-5z"></path>
                                <path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"></path>
                            </svg>
                        </span>
                        <span class="tab-label">Student</span>
                    </button>

                    <button
                        class="tab-btn"
                        id="tab-admin-btn"
                        type="button"
                        onclick="switchTab('admin')"
                        role="tab"
                        aria-selected="false"
                        aria-controls="admin-login"
                    >
                        <span class="tab-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="tab-icon-svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z"></path>
                                <path d="M4 20a8 8 0 0 1 16 0"></path>
                            </svg>
                        </span>
                        <span class="tab-label">Admin</span>
                    </button>
                </div>

                <!-- Student Login Form -->
                <div id="student-login" class="tab-content active">
                    <form action="../Pages/process-student-login.php" method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="student_id">
                                Student ID <span class="required-indicator" aria-hidden="true">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="student_id" 
                                id="student_id" 
                                placeholder="e.g., 22-1-00065" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="student_password">
                                Password <span class="required-indicator" aria-hidden="true">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="password" 
                                id="student_password" 
                                placeholder="Enter your password" 
                                required
                            >
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="auth-btn">Sign In as Student</button>
                        </div>

                        <div class="form-footer">
                            <p class="footer-text">Don't have an account?</p>
                            <a href="../Pages/register.php" class="register-link">Register here</a>
                        </div>
                    </form>
                </div>

                <!-- Admin Login Form -->
                <div id="admin-login" class="tab-content">
                    <form action="../Pages/process-admin-login.php" method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="admin_email">
                                Admin Email <span class="required-indicator" aria-hidden="true">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                id="admin_email" 
                                placeholder="Enter your admin email address" 
                                value="admin@foundit.edu.ph"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="admin_password">
                                Password <span class="required-indicator" aria-hidden="true">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="password" 
                                id="admin_password" 
                                placeholder="Enter your admin password" 
                                required
                            >
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="auth-btn admin-btn">Sign In as Admin</button>
                        </div>

                        <div class="form-footer">
                            <p class="help-text">
                                <a href="#" class="forgot-link">Forgot admin password?</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Tab switching
function switchTab(tab) {
    const studentTab = document.getElementById('student-login');
    const adminTab = document.getElementById('admin-login');
    const studentBtn = document.getElementById('tab-student-btn');
    const adminBtn = document.getElementById('tab-admin-btn');

    studentTab.classList.remove('active');
    adminTab.classList.remove('active');

    studentBtn.classList.remove('active');
    adminBtn.classList.remove('active');

    studentBtn.setAttribute('aria-selected', 'false');
    adminBtn.setAttribute('aria-selected', 'false');

    document.getElementById(tab + '-login').classList.add('active');
    document.getElementById('tab-' + tab + '-btn').classList.add('active');
    document.getElementById('tab-' + tab + '-btn').setAttribute('aria-selected', 'true');
}

// For students: only allow numbers in password field
document.getElementById('student_password')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^0-9]/g, '');
    if (value.length > 8) value = value.slice(0, 8);
    e.target.value = value;
});

// Form loading states
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        if (this.checkValidity()) {
            btn.disabled = true;
            btn.textContent = 'Processing...';
        }
    });
});

// For students: only allow numbers in password field
document.getElementById('student_password')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^0-9]/g, '');
    if (value.length > 8) value = value.slice(0, 8);
    e.target.value = value;
});

// Form loading states
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        
        if (this.checkValidity()) {
            btn.disabled = true;
            btn.textContent = 'Processing...';
        }
    });
});
</script>

<style>
/* Login Tabs */
.login-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 26px;
    padding: 6px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
}

.tab-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 52px;
    padding: 12px 16px;
    background: transparent;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.98rem;
    color: var(--split-char-2, #4A5568);
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    text-align: center;
}

.tab-btn:hover {
    color: var(--mono-red-3, #9B2C2C);
    background: #FFF5F5;
}

.tab-btn.active {
    background: #FFFFFF;
    color: var(--mono-red-3, #9B2C2C);
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
}

.tab-btn.active::after {
    display: none;
}

.tab-btn:focus-visible {
    outline: 3px solid rgba(155, 44, 44, 0.18);
    outline-offset: 2px;
}

.tab-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.tab-icon-svg {
    width: 20px;
    height: 20px;
    display: block;
}

.tab-label {
    line-height: 1.2;
}

@media (max-width: 480px) {
    .login-tabs {
        grid-template-columns: 1fr;
    }

    .tab-btn {
        min-height: 48px;
        font-size: 0.94rem;
    }
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

.admin-btn {
    background: #2C3E50 !important;
}

.admin-btn:hover {
    background: #34495E !important;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
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
    margin: 2px 0;
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