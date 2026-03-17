<?php

$project_id = (int)($_GET['project_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$project_id) {
    header('Location: ?page=dashboard');
    exit;
}

$project = db_query_one('SELECT id FROM projects WHERE id = ?', [$project_id]);

if (!$project) {
    set_flash('error', 'Project not found.');
    header('Location: ?page=dashboard');
    exit;
}

$content = trim($_POST['content'] ?? '');

if ($content === '') {
    set_flash('error', 'Note cannot be empty.');
    header('Location: ?page=project&action=view&id=' . $project_id);
    exit;
}

db_execute('INSERT INTO notes (project_id, content) VALUES (?, ?)', [$project_id, $content]);
set_flash('success', 'Note added.');
header('Location: ?page=project&action=view&id=' . $project_id);
exit;
