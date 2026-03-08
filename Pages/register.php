<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$pdo = db();
$errors = [];
$success = false;
$formData = $_POST; // Store form data for repopulation

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Enhanced Validation with specific error messages
    if ($student_id === '') {
        $errors['student_id'] = "Student ID is required";
    } elseif (!preg_match('/^[0-9-]+$/', $student_id)) {
        $errors['student_id'] = "Student ID should contain only numbers and hyphens";
    }
    
    if ($first_name === '') {
        $errors['first_name'] = "First name is required";
    } elseif (!preg_match('/^[a-zA-Z\s-]+$/', $first_name)) {
        $errors['first_name'] = "First name should contain only letters, spaces, and hyphens";
    }
    
    if ($last_name === '') {
        $errors['last_name'] = "Last name is required";
    } elseif (!preg_match('/^[a-zA-Z\s-]+$/', $last_name)) {
        $errors['last_name'] = "Last name should contain only letters, spaces, and hyphens";
    }
    
    if ($email === '') {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address (e.g., name@domain.com)";
    }
    
    if ($birthdate === '') {
        $errors['birthdate'] = "Birthdate is required";
    } else {
        $dateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$dateObj) {
            $errors['birthdate'] = "Please enter a valid date";
        } else {
            $minDate = new DateTime('-100 years');
            $maxDate = new DateTime('-15 years');
            if ($dateObj < $minDate) {
                $errors['birthdate'] = "Date seems too far in the past";
            } elseif ($dateObj > $maxDate) {
                $errors['birthdate'] = "You must be at least 15 years old to register";
            }
        }
    }

    // Phone validation (optional but format check)
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        $errors['phone'] = "Phone number contains invalid characters";
    }

    // Check if student_id or email already exists
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
        $check->execute([$student_id, $email]);
        if ($existing = $check->fetch()) {
            // Determine which field is duplicate
            $check_student = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
            $check_student->execute([$student_id]);
            if ($check_student->fetch()) {
                $errors['student_id'] = "This Student ID is already registered";
            }
            
            $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->execute([$email]);
            if ($check_email->fetch()) {
                $errors['email'] = "This email address is already registered";
            }
        }
    }

    if (empty($errors)) {
        try {
            $dateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
            if ($dateObj) {
                $month = (int)$dateObj->format('m');
                $day = (int)$dateObj->format('d');
                $year = $dateObj->format('Y');
                $plainPassword = $month . $day . $year;
                $hashed_password = password_hash($plainPassword, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (student_id, first_name, last_name, email, birthdate, phone, password, role, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 1)
                ");
                $stmt->execute([$student_id, $first_name, $last_name, $email, $birthdate, $phone, $hashed_password]);
                
                $success = true;
                $formData = []; // Clear form on success
            }
        } catch (PDOException $e) {
            $errors['general'] = "Registration failed. Please try again later.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<main class="auth-page">
    <section class="auth-hero">
        <div class="container auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>📝 Student Registration</h2>
                    <p>Join our community! Register with your student ID and birthdate.</p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="auth-alert error" role="alert">
                        <strong>⚠️ Error:</strong> <?= h($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="auth-alert success" role="alert">
                        <strong>✅ Registration successful!</strong> 
                        <p style="margin-top: 8px;">Your password has been created based on your birthdate. Please save it securely.</p>
                        <a href="../Login/login.php" class="btn-success" style="display: inline-block; margin-top: 10px;">Proceed to Login →</a>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form" novalidate>
                    <div class="form-group <?= isset($errors['student_id']) ? 'has-error' : '' ?>">
                        <label for="student_id">
                            Student ID <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="student_id" 
                               id="student_id" 
                               value="<?= h($formData['student_id'] ?? '') ?>" 
                               required 
                               aria-required="true"
                               aria-describedby="<?= isset($errors['student_id']) ? 'student_id-error' : '' ?>"
                               placeholder="e.g., 26-1-00001"
                               autocomplete="off">
                        <?php if (isset($errors['student_id'])): ?>
                            <div class="error-message" id="student_id-error" role="alert">
                                ❌ <?= h($errors['student_id']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group <?= isset($errors['first_name']) ? 'has-error' : '' ?>">
                            <label for="first_name">
                                First Name <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="first_name" 
                                   id="first_name" 
                                   value="<?= h($formData['first_name'] ?? '') ?>" 
                                   required
                                   aria-required="true"
                                   aria-describedby="<?= isset($errors['first_name']) ? 'first_name-error' : '' ?>"
                                   placeholder="John"
                                   autocomplete="given-name">
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="error-message" id="first_name-error" role="alert">
                                    ❌ <?= h($errors['first_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?= isset($errors['last_name']) ? 'has-error' : '' ?>">
                            <label for="last_name">
                                Last Name <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="last_name" 
                                   id="last_name" 
                                   value="<?= h($formData['last_name'] ?? '') ?>" 
                                   required
                                   aria-required="true"
                                   aria-describedby="<?= isset($errors['last_name']) ? 'last_name-error' : '' ?>"
                                   placeholder="Doe"
                                   autocomplete="family-name">
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="error-message" id="last_name-error" role="alert">
                                    ❌ <?= h($errors['last_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                        <label for="email">
                            Email Address <span class="required">*</span>
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="<?= h($formData['email'] ?? '') ?>" 
                               required
                               aria-required="true"
                               aria-describedby="<?= isset($errors['email']) ? 'email-error' : '' ?> email-format"
                               placeholder="john.doe@example.com"
                               autocomplete="email">
                        <small id="email-format" class="hint">We'll never share your email with anyone else.</small>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message" id="email-error" role="alert">
                                ❌ <?= h($errors['email']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group <?= isset($errors['birthdate']) ? 'has-error' : '' ?>">
                        <label for="birthdate">
                            Birthdate <span class="required">*</span>
                        </label>
                        <input type="date" 
                               name="birthdate" 
                               id="birthdate" 
                               value="<?= h($formData['birthdate'] ?? '') ?>" 
                               required
                               aria-required="true"
                               aria-describedby="<?= isset($errors['birthdate']) ? 'birthdate-error' : '' ?> password-info"
                               onchange="updatePasswordPreview()"
                               max="<?= date('Y-m-d', strtotime('-15 years')) ?>"
                               min="<?= date('Y-m-d', strtotime('-100 years')) ?>">
                        
                        <div class="password-info" id="password-info">
                            <div class="info-box">
                                <strong>🔐 Your password will be:</strong>
                                <div id="passwordPreview" class="password-preview">
                                    <span id="previewText">Select your birthdate</span>
                                </div>
                                <small class="hint">Format: MMDDYYYY (e.g., April 17, 2003 = 4172003)</small>
                            </div>
                        </div>
                        
                        <?php if (isset($errors['birthdate'])): ?>
                            <div class="error-message" id="birthdate-error" role="alert">
                                ❌ <?= h($errors['birthdate']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                        <label for="phone">
                            Phone Number <span class="optional">(optional)</span>
                        </label>
                        <input type="tel" 
                               name="phone" 
                               id="phone" 
                               value="<?= h($formData['phone'] ?? '') ?>"
                               aria-describedby="<?= isset($errors['phone']) ? 'phone-error' : '' ?> phone-format"
                               placeholder="+63 123 456 7890"
                               autocomplete="tel">
                        <small id="phone-format" class="hint">Include country code for international numbers</small>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="error-message" id="phone-error" role="alert">
                                ❌ <?= h($errors['phone']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="auth-btn">
                            <span class="btn-text">Complete Registration</span>
                            <span class="btn-loader" style="display: none;">⏳</span>
                        </button>
                        
                        <div class="form-footer">
                            <p class="footer-login-link">
                                Already have an account? <a href="../Login/login.php">Sign in here</a>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
// Enhanced password preview with real-time updates
function updatePasswordPreview() {
    const birthdate = document.getElementById('birthdate').value;
    const previewText = document.getElementById('previewText');
    const previewBox = document.getElementById('passwordPreview');
    
    if (birthdate) {
        const date = new Date(birthdate);
        if (!isNaN(date.getTime())) {
            const month = date.getMonth() + 1;
            const day = date.getDate();
            const year = date.getFullYear();
            
            const password = `${month}${day}${year}`;
            previewText.textContent = password;
            previewBox.classList.add('has-value');
        } else {
            previewText.textContent = 'Invalid date selected';
            previewBox.classList.remove('has-value');
        }
    } else {
        previewText.textContent = 'Select your birthdate';
        previewBox.classList.remove('has-value');
    }
}

// Form submission feedback
document.querySelector('.auth-form').addEventListener('submit', function(e) {
    const btn = this.querySelector('.auth-btn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');
    
    if (this.checkValidity()) {
        btn.disabled = true;
        btnText.textContent = 'Registering...';
        btnLoader.style.display = 'inline-block';
    }
});

// Real-time validation feedback
document.querySelectorAll('.form-group input').forEach(input => {
    input.addEventListener('blur', function() {
        const group = this.closest('.form-group');
        if (this.required && !this.value) {
            group.classList.add('touched');
        }
    });
    
    input.addEventListener('input', function() {
        const group = this.closest('.form-group');
        group.classList.remove('has-error');
        const errorMsg = group.querySelector('.error-message');
        if (errorMsg) errorMsg.remove();
    });
});

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePasswordPreview();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>