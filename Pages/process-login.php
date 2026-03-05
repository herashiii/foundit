<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Login/login.php');
    exit;
}

$student_id = trim($_POST['student_id'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($student_id === '' || $password === '') {
    header('Location: ../Login/login.php?error=' . urlencode('Student ID and birthdate are required'));
    exit;
}

$pdo = db();

// Find user by student_id
$stmt = $pdo->prepare("SELECT id, student_id, first_name, last_name, email, password, role FROM users WHERE student_id = ? AND is_active = 1");
$stmt->execute([$student_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../Login/login.php?error=' . urlencode('Invalid Student ID or birthdate'));
    exit;
}

// Verify password - try direct match first
if (password_verify($password, $user['password'])) {
    // Password correct
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // Redirect to previous page or home
    $redirect = $_SESSION['redirect_after_login'] ?? '../Pages/index.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
} else {
    // If direct match fails, try without leading zeros (in case user entered with leading zeros)
    $cleanPassword = preg_replace('/[^0-9]/', '', $password);
    if (strlen($cleanPassword) === 8) {
        // Try format without leading zeros (e.g., 04172003 -> 4172003)
        $alternative1 = ltrim(substr($cleanPassword, 0, 2), '0') . 
                        ltrim(substr($cleanPassword, 2, 2), '0') . 
                        substr($cleanPassword, 4, 4);
        if (password_verify($alternative1, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            $redirect = $_SESSION['redirect_after_login'] ?? '../Pages/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        }
    }
    
    // If all attempts fail
    header('Location: ../Login/login.php?error=' . urlencode('Invalid Student ID or birthdate'));
    exit;
}
?>