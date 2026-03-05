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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validation
    if ($student_id === '') $errors[] = "Student ID is required";
    if ($first_name === '') $errors[] = "First name is required";
    if ($last_name === '') $errors[] = "Last name is required";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if ($birthdate === '') $errors[] = "Birthdate is required";
    
    // Check if student_id or email already exists
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
        $check->execute([$student_id, $email]);
        if ($check->fetch()) {
            $errors[] = "Student ID or Email already registered";
        }
    }

    if (empty($errors)) {
        try {
            // Convert birthdate to MMDDYYYY format for password
            $dateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
            if ($dateObj) {
                $month = (int)$dateObj->format('m'); // Remove leading zero
                $day = (int)$dateObj->format('d');   // Remove leading zero
                $year = $dateObj->format('Y');
                $plainPassword = $month . $day . $year; // e.g., 4172003
                $hashed_password = password_hash($plainPassword, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (student_id, first_name, last_name, email, birthdate, phone, password, role, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 1)
                ");
                $stmt->execute([$student_id, $first_name, $last_name, $email, $birthdate, $phone, $hashed_password]);
                
                $success = true;
            } else {
                $errors[] = "Invalid birthdate format";
            }
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<main class="auth-page">
    <section class="auth-hero">
        <div class="container auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Student Registration</h2>
                    <p>Register with your student ID and birthdate.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="auth-alert error">
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($errors as $e): ?>
                                <li><?= h($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="auth-alert success">
                        <p>Registration successful! <a href="login.php">Login here</a></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" name="student_id" id="student_id" value="<?= h($_POST['student_id'] ?? '') ?>" required>
                    </div>

                    <div class="form-row" style="display: flex; gap: 10px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="first_name">First Name</label>
                            <input type="text" name="first_name" id="first_name" value="<?= h($_POST['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="last_name">Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="<?= h($_POST['last_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" value="<?= h($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="birthdate">Birthdate</label>
                        <input type="date" name="birthdate" id="birthdate" value="<?= h($_POST['birthdate'] ?? '') ?>" required onchange="updatePasswordPreview()">
                        <small style="color: #666; display: block; margin-top: 4px;">
                            Your password will be: MMDDYYYY (e.g., April 17, 2003 = 4172003)
                        </small>
                        <div id="passwordPreview" style="margin-top: 8px; padding: 8px; background: #f5f5f5; border-radius: 4px; display: none;">
                            <strong>Your password will be:</strong> <span id="previewText"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number (optional)</label>
                        <input type="text" name="phone" id="phone" value="<?= h($_POST['phone'] ?? '') ?>">
                    </div>

                    <button type="submit" class="auth-btn">Register</button>
                    
                    <p style="text-align: center; margin-top: 20px;">
                        Already have an account? <a href="../Login/login.php">Login here</a>
                    </p>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
function updatePasswordPreview() {
    const birthdate = document.getElementById('birthdate').value;
    const preview = document.getElementById('passwordPreview');
    const previewText = document.getElementById('previewText');
    
    if (birthdate) {
        const date = new Date(birthdate);
        const month = date.getMonth() + 1; // Month is 0-indexed
        const day = date.getDate();
        const year = date.getFullYear();
        
        // Format as MMDDYYYY without leading zeros
        const password = `${month}${day}${year}`;
        previewText.textContent = password;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>