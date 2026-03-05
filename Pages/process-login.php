<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Login/login.php');
    exit;
}

$student_id = trim($_POST['student_id'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate input
if ($student_id === '' || $password === '') {
    header('Location: ../Login/login.php?error=' . urlencode('Please enter both Student ID and password'));
    exit;
}

try {
    $pdo = db();

    // Find user by student_id
    $stmt = $pdo->prepare("SELECT id, student_id, first_name, last_name, email, password, role FROM users WHERE student_id = ? AND is_active = 1");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // User not found - suggest registration
        header('Location: ../Login/login.php?error=' . urlencode('Student ID not found. Please check your ID or register first.'));
        exit;
    }

    // Verify password - try direct match first
    $passwordVerified = false;

    if (password_verify($password, $user['password'])) {
        $passwordVerified = true;
    } else {
        // Try without leading zeros (in case user entered with leading zeros)
        $cleanPassword = preg_replace('/[^0-9]/', '', $password);
        if (strlen($cleanPassword) === 8) {
            // Try format without leading zeros (e.g., 04172003 -> 4172003)
            $alternative1 = ltrim(substr($cleanPassword, 0, 2), '0') . 
                            ltrim(substr($cleanPassword, 2, 2), '0') . 
                            substr($cleanPassword, 4, 4);
            if (password_verify($alternative1, $user['password'])) {
                $passwordVerified = true;
            }
        }
    }

    if ($passwordVerified) {
        // Password correct - set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Redirect to previous page or home
        $redirect = $_SESSION['redirect_after_login'] ?? '../Pages/index.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    } else {
        // Password incorrect
        header('Location: ../Login/login.php?error=' . urlencode('Incorrect password. Please check your birthdate format.'));
        exit;
    }
} catch (PDOException $e) {
    // Database error
    error_log("Login database error: " . $e->getMessage());
    header('Location: ../Login/login.php?error=' . urlencode('A system error occurred. Please try again later.'));
    exit;
} catch (Exception $e) {
    // Other errors
    error_log("Login error: " . $e->getMessage());
    header('Location: ../Login/login.php?error=' . urlencode('An unexpected error occurred. Please try again.'));
    exit;
}
?>