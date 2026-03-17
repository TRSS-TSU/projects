<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in
if (!empty($_SESSION['authenticated'])) {
    header('Location: ?page=dashboard');
    exit;
}

$error = '';
$timeout = !empty($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (attempt_login($password)) {
        header('Location: ?page=dashboard');
        exit;
    }
    $error = 'Invalid password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Project Tracker</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-body">
<div class="login-box">
    <h1>Project Tracker</h1>
    <?php if ($timeout): ?>
        <div class="flash flash-error">Session expired. Please log in again.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="?page=login">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autofocus required>
        <button type="submit">Log In</button>
    </form>
</div>
</body>
</html>
