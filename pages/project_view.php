<?php

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$project = db_query_one(
    "SELECT p.*, sq.name AS squadron_name, s.name AS status_name
     FROM projects p
     LEFT JOIN squadrons sq ON sq.id = p.squadron_id
     LEFT JOIN statuses s ON s.id = p.status_id
     WHERE p.id = ?",
    [$id]
);

if (!$project) {
    set_flash('error', 'Project not found.');
    header('Location: ?page=dashboard');
    exit;
}

$pocs = db_query(
    "SELECT poc.name, poc.phone, poc.flight FROM pocs poc
     JOIN project_pocs pp ON pp.poc_id = poc.id
     WHERE pp.project_id = ? ORDER BY poc.name",
    [$id]
);

$courses = db_query(
    "SELECT c.name FROM courses c
     JOIN project_courses pc ON pc.course_id = c.id
     WHERE pc.project_id = ? ORDER BY c.name",
    [$id]
);

$tags = db_query(
    "SELECT st.name FROM software_tags st
     JOIN project_software_tags pst ON pst.tag_id = st.id
     WHERE pst.project_id = ? ORDER BY st.name",
    [$id]
);

$targets = db_query(
    "SELECT dt.name FROM deployment_targets dt
     JOIN project_deployment_targets pdt ON pdt.target_id = dt.id
     WHERE pdt.project_id = ? ORDER BY dt.name",
    [$id]
);

$notes = db_query('SELECT * FROM notes WHERE project_id = ? ORDER BY created_at DESC', [$id]);

ob_start();
?>
<div class="page-header">
    <h1><?= e($project['project_number']) ?><?= $project['project_name'] ? ' — ' . e($project['project_name']) : '' ?></h1>
    <div class="page-header-actions">
        <a href="?page=project&action=edit&id=<?= $id ?>" class="btn-secondary">Edit</a>
        <a href="?page=project&action=delete&id=<?= $id ?>" class="btn-danger">Delete</a>
        <a href="?page=dashboard" class="btn-secondary">← Dashboard</a>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-section">
        <table class="detail-table">
            <tr><th>Squadron</th><td><?= e($project['squadron_name'] ?? '—') ?></td></tr>
            <tr>
                <th>POC(s)</th>
                <td>
                <?php if ($pocs): ?>
                    <ul class="poc-list">
                    <?php foreach ($pocs as $poc): ?>
                        <li>
                            <strong><?= e($poc['name']) ?></strong>
                            <?php if ($poc['flight']): ?><span class="poc-flight"><?= e($poc['flight']) ?></span><?php endif; ?>
                            <?php if ($poc['phone']): ?><span class="poc-phone"><?= e($poc['phone']) ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <tr><th>Status</th><td><span class="status-badge"><?= e($project['status_name'] ?? '—') ?></span></td></tr>
            <tr><th>Start Date</th><td><?= format_date($project['start_date']) ?></td></tr>
            <tr><th>Completion</th><td><?= format_date($project['completion_date']) ?></td></tr>
            <tr>
                <th>Courses</th>
                <td><?= $courses ? implode(', ', array_map(fn($c) => e($c['name']), $courses)) : '—' ?></td>
            </tr>
            <tr>
                <th>Software</th>
                <td><?= $tags ? implode(', ', array_map(fn($t) => e($t['name']), $tags)) : '—' ?></td>
            </tr>
            <tr>
                <th>Targets</th>
                <td><?= $targets ? implode(', ', array_map(fn($t) => e($t['name']), $targets)) : '—' ?></td>
            </tr>
        </table>
    </div>
    <div class="detail-description">
        <h3>Description</h3>
        <p><?= nl2br(e($project['description'])) ?></p>
    </div>
</div>

<section class="notes-section">
    <h2>Notes</h2>

    <?php if (empty($notes)): ?>
        <p class="empty-state">No notes yet.</p>
    <?php else: ?>
        <div class="notes-list">
            <?php foreach ($notes as $note): ?>
            <div class="note">
                <div class="note-meta"><?= format_ts($note['created_at']) ?></div>
                <div class="note-content"><?= nl2br(e($note['content'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="?page=note&action=add&project_id=<?= $id ?>" class="note-form">
        <label for="note_content">Add Note</label>
        <textarea id="note_content" name="content" rows="4" placeholder="Write a note…" required></textarea>
        <button type="submit">Add Note</button>
    </form>
</section>
<?php

$content = ob_get_clean();
$title   = $project['project_number'];
require APP_ROOT . '/templates/layout.php';
