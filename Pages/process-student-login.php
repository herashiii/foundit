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
    header('Location: ../Login/login.php?error=' . urlencode('Please enter both Student ID and password'));
    exit;
}

try {
    $pdo = db();

    // Find student user
    $stmt = $pdo->prepare("
        SELECT id, student_id, first_name, last_name, email, password, role, is_active 
        FROM users 
        WHERE student_id = ? AND role = 'user' AND is_active = 1
    ");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: ../Login/login.php?error=' . urlencode('Student account not found.'));
        exit;
    }

    // Verify password
    $passwordVerified = false;

    if (password_verify($password, $user['password'])) {
        $passwordVerified = true;
    } else {
        $cleanPassword = preg_replace('/[^0-9]/', '', $password);
        if (strlen($cleanPassword) === 8) {
            $alternative = ltrim(substr($cleanPassword, 0, 2), '0') . 
                           ltrim(substr($cleanPassword, 2, 2), '0') . 
                           substr($cleanPassword, 4, 4);
            if (password_verify($alternative, $user['password'])) {
                $passwordVerified = true;
            }
        }
    }

    if ($passwordVerified) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        session_regenerate_id(true);
        
        $redirect = $_SESSION['redirect_after_login'] ?? '../Pages/index.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    } else {
        header('Location: ../Login/login.php?error=' . urlencode('Incorrect birthdate. Please check your format.'));
        exit;
    }

} catch (Exception $e) {
    error_log("Student login error: " . $e->getMessage());
    header('Location: ../Login/login.php?error=' . urlencode('Login failed. Please try again.'));
    exit;
}
?>