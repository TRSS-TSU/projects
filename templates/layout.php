<?php
// Expected variables: $title (string), $content (string)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Project Tracker') ?> — Project Tracker</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<nav class="nav">
    <div class="nav-brand"><a href="?page=dashboard">Project Tracker</a></div>
    <ul class="nav-links">
        <li><a href="?page=dashboard">Dashboard</a></li>
        <li><a href="?page=project&action=new">New Project</a></li>
        <li><a href="?page=settings">Settings</a></li>
        <li><a href="?page=requests">Requests</a></li>
        <li><a href="?page=public" target="_blank">Public View</a></li>
        <li><a href="?page=export">Export</a></li>
        <li><a href="?page=logout" class="nav-logout">Logout</a></li>
    </ul>
</nav>
<main class="main">
    <?php require __DIR__ . '/flash.php'; ?>
    <?= $content ?>
</main>
<script src="/js/app.js"></script>
</body>
</html>
