<?php

$db = get_db();

$projects = db_query(
    "SELECT p.*, sq.name AS squadron_name, s.name AS status_name
     FROM projects p
     LEFT JOIN squadrons sq ON sq.id = p.squadron_id
     LEFT JOIN statuses s ON s.id = p.status_id
     WHERE p.completion_date IS NULL OR p.completion_date = ''
     ORDER BY p.created_at DESC"
);

foreach ($projects as &$p) {
    $p['pocs'] = db_query(
        "SELECT poc.name, poc.phone, poc.flight FROM pocs poc
         JOIN project_pocs pp ON pp.poc_id = poc.id
         WHERE pp.project_id = ? ORDER BY poc.name",
        [$p['id']]
    );
    $p['courses'] = db_query(
        "SELECT c.name FROM courses c
         JOIN project_courses pc ON pc.course_id = c.id
         WHERE pc.project_id = ? ORDER BY c.name",
        [$p['id']]
    );
    $p['latest_note'] = db_query_one(
        'SELECT content, created_at FROM notes WHERE project_id = ? ORDER BY created_at DESC LIMIT 1',
        [$p['id']]
    );
}
unset($p);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Projects</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="pub-header">
    <div class="pub-header-inner">
        <span class="pub-title">Active Projects</span>
        <span class="pub-count"><?= count($projects) ?> open</span>
    </div>
</header>

<main class="pub-main">
    <?php if (empty($projects)): ?>
        <p class="empty-state" style="text-align:center;padding:3rem 0">No open projects at this time.</p>
    <?php else: ?>
    <div class="pub-grid">
        <?php foreach ($projects as $p): ?>
        <div class="pub-card">
            <div class="pub-card-top">
                <span class="status-badge"><?= e($p['status_name'] ?? '—') ?></span>
                <span class="pub-card-date"><?= format_date($p['start_date']) ?></span>
            </div>

            <h2 class="pub-card-name"><?= e($p['project_name'] ?: $p['project_number']) ?></h2>
            <div class="pub-card-number"><?= e($p['project_number']) ?></div>

            <div class="pub-card-meta">
                <div class="pub-card-meta-row">
                    <span class="pub-card-meta-label">Squadron</span>
                    <span class="pub-card-meta-value"><?= e($p['squadron_name'] ?? '—') ?></span>
                </div>

                <div class="pub-card-meta-row">
                    <span class="pub-card-meta-label">POC<?= count($p['pocs']) > 1 ? 's' : '' ?></span>
                    <span class="pub-card-meta-value">
                    <?php if ($p['pocs']): ?>
                        <ul class="pub-poc-list">
                        <?php foreach ($p['pocs'] as $poc): ?>
                            <li>
                                <span class="pub-poc-name"><?= e($poc['name']) ?></span>
                                <?php if ($poc['flight']): ?>
                                    <span class="pub-poc-flight"><?= e($poc['flight']) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>—<?php endif; ?>
                    </span>
                </div>

                <?php if ($p['courses']): ?>
                <div class="pub-card-meta-row">
                    <span class="pub-card-meta-label">Course<?= count($p['courses']) > 1 ? 's' : '' ?></span>
                    <span class="pub-card-meta-value">
                        <div class="pub-course-list">
                        <?php foreach ($p['courses'] as $c): ?>
                            <span class="pub-course-tag"><?= e($c['name']) ?></span>
                        <?php endforeach; ?>
                        </div>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($p['latest_note']): ?>
            <div class="pub-card-note">
                <div class="pub-card-note-meta"><?= format_ts($p['latest_note']['created_at']) ?></div>
                <div class="pub-card-note-text"><?= nl2br(e($p['latest_note']['content'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
