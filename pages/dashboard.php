<?php

$db = get_db();

// Filter params
$search         = trim($_GET['search']   ?? '');
$filter_status  = (int)($_GET['status']  ?? 0);
$filter_squadron = (int)($_GET['squadron'] ?? 0);
$filter_tag     = (int)($_GET['tag']     ?? 0);
$filter_target  = (int)($_GET['target']  ?? 0);

// Build query
$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(p.project_number LIKE ? OR p.project_name LIKE ? OR p.description LIKE ? OR sq.name LIKE ? OR poc.name LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($filter_status) {
    $where[]  = 'p.status_id = ?';
    $params[] = $filter_status;
}
if ($filter_squadron) {
    $where[]  = 'p.squadron_id = ?';
    $params[] = $filter_squadron;
}
if ($filter_tag) {
    $where[]  = 'EXISTS (SELECT 1 FROM project_software_tags pst WHERE pst.project_id = p.id AND pst.tag_id = ?)';
    $params[] = $filter_tag;
}
if ($filter_target) {
    $where[]  = 'EXISTS (SELECT 1 FROM project_deployment_targets pdt WHERE pdt.project_id = p.id AND pdt.target_id = ?)';
    $params[] = $filter_target;
}

$sql = "SELECT p.*, sq.name AS squadron_name, poc.name AS poc_name, s.name AS status_name
        FROM projects p
        LEFT JOIN squadrons sq ON sq.id = p.squadron_id
        LEFT JOIN pocs poc ON poc.id = p.poc_id
        LEFT JOIN statuses s ON s.id = p.status_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.start_date DESC, p.project_number ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Filter options
$statuses  = db_query('SELECT * FROM statuses ORDER BY sort_order, name');
$squadrons = db_query('SELECT * FROM squadrons ORDER BY name');
$tags      = db_query('SELECT * FROM software_tags ORDER BY name');
$targets   = db_query('SELECT * FROM deployment_targets ORDER BY name');

ob_start();
?>
<div class="page-header">
    <h1>Dashboard</h1>
</div>

<form class="filter-form" method="get" action="">
    <input type="hidden" name="page" value="dashboard">
    <input type="text" name="search" placeholder="Search…" value="<?= e($search) ?>">

    <select name="status">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $filter_status == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="squadron">
        <option value="">All Squadrons</option>
        <?php foreach ($squadrons as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $filter_squadron == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="tag">
        <option value="">All Tags</option>
        <?php foreach ($tags as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filter_tag == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="target">
        <option value="">All Targets</option>
        <?php foreach ($targets as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filter_target == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filter</button>
    <a href="?page=dashboard" class="btn-secondary">Reset</a>
</form>

<?php if (empty($projects)): ?>
    <p class="empty-state">No projects found. <a href="?page=project&action=new">Create one.</a></p>
<?php else: ?>
<table class="data-table">
    <thead>
        <tr>
            <th>Project #</th>
            <th>Project Name</th>
            <th>Squadron</th>
            <th>POC</th>
            <th>Description</th>
            <th>Status</th>
            <th>Start</th>
            <th>Completion</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($projects as $p): ?>
        <tr>
            <td><a href="?page=project&action=view&id=<?= $p['id'] ?>"><?= e($p['project_number']) ?></a></td>
            <td><?= e($p['project_name'] ?? '') ?></td>
            <td><?= e($p['squadron_name'] ?? '—') ?></td>
            <td><?= e($p['poc_name'] ?? '—') ?></td>
            <td class="col-wrap"><?= e($p['description']) ?></td>
            <td><span class="status-badge"><?= e($p['status_name'] ?? '—') ?></span></td>
            <td><?= format_date($p['start_date']) ?></td>
            <td><?= format_date($p['completion_date']) ?></td>
            <td class="actions">
                <a href="?page=project&action=edit&id=<?= $p['id'] ?>">Edit</a>
                <a href="?page=project&action=delete&id=<?= $p['id'] ?>" class="danger-link">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php

$content = ob_get_clean();
$title   = 'Dashboard';
require APP_ROOT . '/templates/layout.php';
