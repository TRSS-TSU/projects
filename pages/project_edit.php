<?php

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$project = db_query_one('SELECT * FROM projects WHERE id = ?', [$id]);

if (!$project) {
    set_flash('error', 'Project not found.');
    header('Location: ?page=dashboard');
    exit;
}

$existing_pocs    = array_column(db_query('SELECT poc_id    FROM project_pocs    WHERE project_id = ?', [$id]), 'poc_id');
$existing_courses = array_column(db_query('SELECT course_id FROM project_courses WHERE project_id = ?', [$id]), 'course_id');
$existing_tags    = array_column(db_query('SELECT tag_id    FROM project_software_tags      WHERE project_id = ?', [$id]), 'tag_id');
$existing_targets = array_column(db_query('SELECT target_id FROM project_deployment_targets WHERE project_id = ?', [$id]), 'target_id');

$errors = [];
$vals = [
    'project_number'   => $project['project_number'],
    'project_name'     => $project['project_name'],
    'squadron_id'      => $project['squadron_id'],
    'squadron_other'   => '',
    'project_pocs'     => $existing_pocs,
    'poc_other_name'   => '',
    'poc_other_phone'  => '',
    'poc_other_flight' => '',
    'description'      => $project['description'],
    'status_id'        => $project['status_id'],
    'start_date'       => $project['start_date'],
    'completion_date'  => $project['completion_date'],
    'project_courses'  => $existing_courses,
    'course_other'     => '',
    'software_tags'    => $existing_tags,
    'tag_other'        => '',
    'deploy_targets'   => $existing_targets,
    'target_other'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['project_number']   = trim($_POST['project_number']   ?? '');
    $vals['project_name']     = trim($_POST['project_name']     ?? '');
    $vals['squadron_id']      = trim($_POST['squadron_id']      ?? '');
    $vals['squadron_other']   = trim($_POST['squadron_other']   ?? '');
    $vals['poc_other_name']   = trim($_POST['poc_other_name']   ?? '');
    $vals['poc_other_phone']  = trim($_POST['poc_other_phone']  ?? '');
    $vals['poc_other_flight'] = trim($_POST['poc_other_flight'] ?? '');
    $vals['description']      = trim($_POST['description']      ?? '');
    $vals['status_id']        = (int)($_POST['status_id']       ?? 0);
    $vals['start_date']       = trim($_POST['start_date']       ?? '');
    $vals['completion_date']  = trim($_POST['completion_date']  ?? '') ?: null;
    $vals['course_other']        = trim($_POST['course_other']        ?? '');
    $vals['course_other_number'] = trim($_POST['course_other_number'] ?? '');
    $vals['tag_other']        = trim($_POST['tag_other']        ?? '');
    $vals['target_other']     = trim($_POST['target_other']     ?? '');

    $raw_pocs    = (array)($_POST['project_pocs']    ?? []);
    $raw_courses = (array)($_POST['project_courses'] ?? []);
    $raw_tags    = (array)($_POST['software_tags']   ?? []);
    $raw_targets = (array)($_POST['deploy_targets']  ?? []);

    $vals['project_pocs']    = array_filter(array_map('intval', array_filter($raw_pocs,    fn($v) => $v !== 'other')));
    $vals['project_courses'] = array_filter(array_map('intval', array_filter($raw_courses, fn($v) => $v !== 'other')));
    $vals['software_tags']   = array_filter(array_map('intval', array_filter($raw_tags,    fn($v) => $v !== 'other')));
    $vals['deploy_targets']  = array_filter(array_map('intval', array_filter($raw_targets, fn($v) => $v !== 'other')));

    if ($vals['project_number'] === '') $errors[] = 'Project number is required.';
    if ($vals['project_name'] === '')   $errors[] = 'Project name is required.';
    if (!$vals['status_id'])            $errors[] = 'Status is required.';
    if ($vals['start_date'] === '')     $errors[] = 'Start date is required.';
    if ($vals['squadron_id'] === 'other' && $vals['squadron_other'] === '') $errors[] = 'New squadron name is required.';
    if (in_array('other', $raw_pocs)    && $vals['poc_other_name'] === '')  $errors[] = 'New POC name is required.';
    if (in_array('other', $raw_courses) && $vals['course_other'] === '')    $errors[] = 'New course name is required.';
    if (in_array('other', $raw_tags)    && $vals['tag_other'] === '')       $errors[] = 'New software tag name is required.';
    if (in_array('other', $raw_targets) && $vals['target_other'] === '')    $errors[] = 'New deployment target name is required.';

    if (empty($errors)) {
        $dup = db_query_one('SELECT id FROM projects WHERE project_number = ? AND id != ?', [$vals['project_number'], $id]);
        if ($dup) $errors[] = 'Project number already in use.';
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $squadron_id = null;
            if ($vals['squadron_id'] === 'other') {
                $squadron_id = db_execute('INSERT OR IGNORE INTO squadrons (name) VALUES (?)', [$vals['squadron_other']]);
                if (!$squadron_id) $squadron_id = db_query_one('SELECT id FROM squadrons WHERE name = ?', [$vals['squadron_other']])['id'];
            } elseif ($vals['squadron_id'] !== '') {
                $squadron_id = (int)$vals['squadron_id'];
            }

            if (in_array('other', $raw_pocs) && $vals['poc_other_name'] !== '') {
                $new_poc_id = db_execute('INSERT OR IGNORE INTO pocs (name, phone, flight) VALUES (?, ?, ?)',
                    [$vals['poc_other_name'], $vals['poc_other_phone'], $vals['poc_other_flight']]);
                if (!$new_poc_id) $new_poc_id = db_query_one('SELECT id FROM pocs WHERE name = ?', [$vals['poc_other_name']])['id'];
                $vals['project_pocs'][] = (int)$new_poc_id;
            }

            if (in_array('other', $raw_courses) && $vals['course_other'] !== '') {
                $new_course_id = db_execute('INSERT OR IGNORE INTO courses (name, course_number) VALUES (?, ?)', [$vals['course_other'], $vals['course_other_number']]);
                if (!$new_course_id) $new_course_id = db_query_one('SELECT id FROM courses WHERE name = ?', [$vals['course_other']])['id'];
                $vals['project_courses'][] = (int)$new_course_id;
            }

            if (in_array('other', $raw_tags) && $vals['tag_other'] !== '') {
                $new_tag_id = db_execute('INSERT OR IGNORE INTO software_tags (name) VALUES (?)', [$vals['tag_other']]);
                if (!$new_tag_id) $new_tag_id = db_query_one('SELECT id FROM software_tags WHERE name = ?', [$vals['tag_other']])['id'];
                $vals['software_tags'][] = (int)$new_tag_id;
            }

            if (in_array('other', $raw_targets) && $vals['target_other'] !== '') {
                $new_target_id = db_execute('INSERT OR IGNORE INTO deployment_targets (name) VALUES (?)', [$vals['target_other']]);
                if (!$new_target_id) $new_target_id = db_query_one('SELECT id FROM deployment_targets WHERE name = ?', [$vals['target_other']])['id'];
                $vals['deploy_targets'][] = (int)$new_target_id;
            }

            db_execute(
                "UPDATE projects SET project_number=?, project_name=?, squadron_id=?,
                 description=?, status_id=?, start_date=?, completion_date=?, updated_at=CURRENT_TIMESTAMP
                 WHERE id=?",
                [$vals['project_number'], $vals['project_name'], $squadron_id,
                 $vals['description'], $vals['status_id'], $vals['start_date'], $vals['completion_date'], $id]
            );

            db_execute('DELETE FROM project_pocs               WHERE project_id = ?', [$id]);
            db_execute('DELETE FROM project_courses            WHERE project_id = ?', [$id]);
            db_execute('DELETE FROM project_software_tags      WHERE project_id = ?', [$id]);
            db_execute('DELETE FROM project_deployment_targets WHERE project_id = ?', [$id]);

            foreach ($vals['project_pocs']    as $pid) db_execute('INSERT INTO project_pocs    (project_id, poc_id)    VALUES (?, ?)', [$id, $pid]);
            foreach ($vals['project_courses']  as $cid) db_execute('INSERT INTO project_courses (project_id, course_id) VALUES (?, ?)', [$id, $cid]);
            foreach ($vals['software_tags']    as $tid) db_execute('INSERT INTO project_software_tags      (project_id, tag_id)    VALUES (?, ?)', [$id, $tid]);
            foreach ($vals['deploy_targets']   as $tid) db_execute('INSERT INTO project_deployment_targets (project_id, target_id) VALUES (?, ?)', [$id, $tid]);

            $db->commit();
            set_flash('success', 'Project updated.');
            header('Location: ?page=project&action=view&id=' . $id);
            exit;
        } catch (Exception $ex) {
            $db->rollBack();
            $errors[] = 'Database error: ' . $ex->getMessage();
        }
    }
}

$statuses  = db_query('SELECT * FROM statuses ORDER BY sort_order, name');
$squadrons = db_query('SELECT * FROM squadrons ORDER BY name');
$pocs      = db_query('SELECT * FROM pocs ORDER BY name');
$courses   = db_query('SELECT * FROM courses ORDER BY name');
$tags      = db_query('SELECT * FROM software_tags ORDER BY name');
$targets   = db_query('SELECT * FROM deployment_targets ORDER BY name');

ob_start();
?>
<div class="page-header">
    <h1>Edit Project <?= e($project['project_number']) ?></h1>
    <a href="?page=project&action=view&id=<?= $id ?>" class="btn-secondary">← Back</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<form method="post" action="?page=project&action=edit&id=<?= $id ?>" class="form-card">
    <?php require APP_ROOT . '/templates/project_form_fields.php'; ?>
    <div class="form-actions">
        <button type="submit">Save Changes</button>
        <a href="?page=project&action=view&id=<?= $id ?>" class="btn-secondary">Cancel</a>
    </div>
</form>
<?php

$content = ob_get_clean();
$title   = 'Edit Project';
require APP_ROOT . '/templates/layout.php';
