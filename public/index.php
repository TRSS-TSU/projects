<?php

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/src/config.php';
require_once APP_ROOT . '/src/db.php';
require_once APP_ROOT . '/src/auth.php';
require_once APP_ROOT . '/src/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page   = $_GET['page']   ?? 'dashboard';
$action = $_GET['action'] ?? '';

// Public routes (no auth)
if ($page === 'public') {
    require APP_ROOT . '/pages/public.php';
    exit;
}
if ($page === 'landing') {
    require APP_ROOT . '/pages/public_landing.php';
    exit;
}
if ($page === 'request') {
    require APP_ROOT . '/pages/public_request.php';
    exit;
}
if ($page === 'thankyou') {
    require APP_ROOT . '/pages/public_thankyou.php';
    exit;
}

if ($page === 'login') {
    require APP_ROOT . '/pages/login.php';
    exit;
}

if ($page === 'logout') {
    require_auth();
    logout();
    header('Location: ?page=login');
    exit;
}

// All other routes require auth
require_auth();

$allowed_pages = ['dashboard', 'project', 'note', 'settings', 'export', 'requests'];

if (!in_array($page, $allowed_pages, true)) {
    header('Location: ?page=dashboard');
    exit;
}

switch ($page) {
    case 'dashboard':
        require APP_ROOT . '/pages/dashboard.php';
        break;
    case 'project':
        if ($action === 'new')    require APP_ROOT . '/pages/project_new.php';
        elseif ($action === 'edit')   require APP_ROOT . '/pages/project_edit.php';
        elseif ($action === 'view')   require APP_ROOT . '/pages/project_view.php';
        elseif ($action === 'delete') require APP_ROOT . '/pages/project_delete.php';
        else { header('Location: ?page=dashboard'); exit; }
        break;
    case 'note':
        if ($action === 'add') require APP_ROOT . '/pages/note_add.php';
        else { header('Location: ?page=dashboard'); exit; }
        break;
    case 'settings':
        require APP_ROOT . '/pages/settings.php';
        break;
    case 'export':
        require APP_ROOT . '/pages/export.php';
        break;
    case 'requests':
        require APP_ROOT . '/pages/requests.php';
        break;
}
