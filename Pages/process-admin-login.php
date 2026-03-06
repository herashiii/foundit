<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Login/login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header('Location: ../Login/login.php?error=' . urlencode('Please enter both email and password'));
    exit;
}

try {
    $pdo = db();

    // Find admin user by email - NO PASSWORD VERIFICATION NEEDED IN QUERY
    $stmt = $pdo->prepare("
        SELECT id, student_id, first_name, last_name, email, password, role, is_active 
        FROM users 
        WHERE email = ? AND role = 'admin' AND is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: ../Login/login.php?error=' . urlencode('Admin account not found.'));
        exit;
    }

    // SIMPLE PLAIN TEXT COMPARISON
    if ($password === $user['password']) {  // Direct string comparison
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        header('Location: ../Pages/admindash.php');
        exit;
    } else {
        header('Location: ../Login/login.php?error=' . urlencode('Incorrect admin password.'));
        exit;
    }

} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    header('Location: ../Login/login.php?error=' . urlencode('Login failed. Please try again.'));
    exit;
}
?>