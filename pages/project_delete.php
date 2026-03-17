<?php

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$project = db_query_one('SELECT * FROM projects WHERE id = ?', [$id]);

if (!$project) {
    set_flash('error', 'Project not found.');
    header('Location: ?page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $db = get_db();
    $db->beginTransaction();
    try {
        db_execute('DELETE FROM notes                      WHERE project_id = ?', [$id]);
        db_execute('DELETE FROM project_pocs               WHERE project_id = ?', [$id]);
        db_execute('DELETE FROM project_courses            WHERE project_id = ?', [$id]);
        db_execute('DELETE FROM project_software_tags      WHERE project_id = ?', [$id]);
        db_execute('DELETE FROM project_deployment_targets WHERE project_id = ?', [$id]);
        db_execute('DELETE FROM projects WHERE id = ?', [$id]);
        $db->commit();
        set_flash('success', 'Project "' . $project['project_number'] . '" deleted.');
        header('Location: ?page=dashboard');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        set_flash('error', 'Delete failed: ' . $e->getMessage());
        header('Location: ?page=project&action=view&id=' . $id);
        exit;
    }
}

ob_start();
?>
<div class="page-header">
    <h1>Delete Project</h1>
</div>

<div class="confirm-box">
    <p>Are you sure you want to delete project <strong><?= e($project['project_number']) ?></strong>?</p>
    <p class="confirm-warning">This will also delete all associated notes. This action cannot be undone.</p>

    <form method="post" action="?page=project&action=delete&id=<?= $id ?>">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="btn-danger">Yes, Delete</button>
        <a href="?page=project&action=view&id=<?= $id ?>" class="btn-secondary">Cancel</a>
    </form>
</div>
<?php

$content = ob_get_clean();
$title   = 'Delete Project';
require APP_ROOT . '/templates/layout.php';
