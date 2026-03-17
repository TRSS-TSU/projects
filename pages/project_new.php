<?php

$db = get_db();
$errors = [];
$from_request_id = (int)($_GET['req'] ?? 0);
$prefill_request = $from_request_id ? db_query_one('SELECT * FROM project_requests WHERE id = ?', [$from_request_id]) : null;

$default_status = db_query_one("SELECT id FROM statuses WHERE name = 'In-Progress'") ??
                  db_query_one("SELECT id FROM statuses ORDER BY sort_order, name LIMIT 1");
$vals = [
    'project_number'  => $prefill_request['course_number'] ?? '',
    'project_name'    => $prefill_request['course_name']   ?? '',
    'squadron_id'     => '',
    'squadron_other'  => '',
    'project_pocs'    => [],
    'poc_other_name'  => $prefill_request['poc_name'] ?? '',
    'poc_other_phone' => '',
    'poc_other_flight'=> '',
    'description'     => $prefill_request['message'] ?? '',
    'status_id'       => $default_status['id'] ?? '',
    'start_date'      => date('Y-m-d'),
    'completion_date' => '',
    'project_courses' => [],
    'course_other'    => '',
    'software_tags'   => [],
    'tag_other'       => '',
    'deploy_targets'  => [],
    'target_other'    => '',
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
        $exists = db_query_one('SELECT id FROM projects WHERE project_number = ?', [$vals['project_number']]);
        if ($exists) $errors[] = 'Project number already exists.';
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Resolve squadron
            $squadron_id = null;
            if ($vals['squadron_id'] === 'other') {
                $squadron_id = db_execute('INSERT OR IGNORE INTO squadrons (name) VALUES (?)', [$vals['squadron_other']]);
                if (!$squadron_id) $squadron_id = db_query_one('SELECT id FROM squadrons WHERE name = ?', [$vals['squadron_other']])['id'];
            } elseif ($vals['squadron_id'] !== '') {
                $squadron_id = (int)$vals['squadron_id'];
            }

            // Resolve Other POC
            if (in_array('other', $raw_pocs) && $vals['poc_other_name'] !== '') {
                $new_poc_id = db_execute('INSERT OR IGNORE INTO pocs (name, phone, flight) VALUES (?, ?, ?)',
                    [$vals['poc_other_name'], $vals['poc_other_phone'], $vals['poc_other_flight']]);
                if (!$new_poc_id) $new_poc_id = db_query_one('SELECT id FROM pocs WHERE name = ?', [$vals['poc_other_name']])['id'];
                $vals['project_pocs'][] = (int)$new_poc_id;
            }

            // Resolve Other course
            if (in_array('other', $raw_courses) && $vals['course_other'] !== '') {
                $new_course_id = db_execute('INSERT OR IGNORE INTO courses (name, course_number) VALUES (?, ?)', [$vals['course_other'], $vals['course_other_number']]);
                if (!$new_course_id) $new_course_id = db_query_one('SELECT id FROM courses WHERE name = ?', [$vals['course_other']])['id'];
                $vals['project_courses'][] = (int)$new_course_id;
            }

            // Resolve Other tag
            if (in_array('other', $raw_tags) && $vals['tag_other'] !== '') {
                $new_tag_id = db_execute('INSERT OR IGNORE INTO software_tags (name) VALUES (?)', [$vals['tag_other']]);
                if (!$new_tag_id) $new_tag_id = db_query_one('SELECT id FROM software_tags WHERE name = ?', [$vals['tag_other']])['id'];
                $vals['software_tags'][] = (int)$new_tag_id;
            }

            // Resolve Other target
            if (in_array('other', $raw_targets) && $vals['target_other'] !== '') {
                $new_target_id = db_execute('INSERT OR IGNORE INTO deployment_targets (name) VALUES (?)', [$vals['target_other']]);
                if (!$new_target_id) $new_target_id = db_query_one('SELECT id FROM deployment_targets WHERE name = ?', [$vals['target_other']])['id'];
                $vals['deploy_targets'][] = (int)$new_target_id;
            }

            $id = db_execute(
                "INSERT INTO projects (project_number, project_name, squadron_id, description, status_id, start_date, completion_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$vals['project_number'], $vals['project_name'], $squadron_id,
                 $vals['description'], $vals['status_id'], $vals['start_date'], $vals['completion_date']]
            );

            foreach ($vals['project_pocs']    as $pid) db_execute('INSERT INTO project_pocs    (project_id, poc_id)    VALUES (?, ?)', [$id, $pid]);
            foreach ($vals['project_courses']  as $cid) db_execute('INSERT INTO project_courses (project_id, course_id) VALUES (?, ?)', [$id, $cid]);
            foreach ($vals['software_tags']    as $tid) db_execute('INSERT INTO project_software_tags      (project_id, tag_id)    VALUES (?, ?)', [$id, $tid]);
            foreach ($vals['deploy_targets']   as $tid) db_execute('INSERT INTO project_deployment_targets (project_id, target_id) VALUES (?, ?)', [$id, $tid]);

            // Mark originating request as converted
            $from_req = (int)($_POST['from_request_id'] ?? 0);
            if ($from_req) {
                db_execute("UPDATE project_requests SET status='converted', updated_at=CURRENT_TIMESTAMP WHERE id=?", [$from_req]);
            }

            $db->commit();
            set_flash('success', 'Project created.');
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
    <h1>New Project<?= $prefill_request ? ' <small style="font-weight:400;font-size:.7em;color:var(--text-muted)">from Request #' . $prefill_request['id'] . '</small>' : '' ?></h1>
    <a href="<?= $prefill_request ? '?page=requests&id=' . $prefill_request['id'] : '?page=dashboard' ?>" class="btn-secondary">← Back</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<form method="post" action="?page=project&action=new" class="form-card">
    <input type="hidden" name="from_request_id" value="<?= $prefill_request ? $prefill_request['id'] : '' ?>">
    <?php require APP_ROOT . '/templates/project_form_fields.php'; ?>
    <div class="form-actions">
        <button type="submit">Create Project</button>
        <a href="?page=dashboard" class="btn-secondary">Cancel</a>
    </div>
</form>
<?php

$content = ob_get_clean();
$title   = 'New Project';
require APP_ROOT . '/templates/layout.php';
