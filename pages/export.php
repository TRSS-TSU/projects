<?php

$db = get_db();
$markdown = generate_markdown_export($db);
$filename = 'projects-export-' . date('Y-m-d') . '.md';

header('Content-Type: text/markdown; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($markdown));
echo $markdown;
exit;
