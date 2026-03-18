<?php

$db = get_db();
$section = $_GET['section'] ?? 'statuses';
$allowed_sections = ['statuses', 'squadrons', 'pocs', 'courses', 'software_tags', 'deployment_targets', 'security'];
if (!in_array($section, $allowed_sections, true)) $section = 'statuses';

// Handle password change before the data-table logic
if ($section === 'security' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password']  ?? '';
    $new_pw   = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';
    $pw_error = '';

    if (!password_verify($current, PASSWORD_HASH)) {
        $pw_error = 'Current password is incorrect.';
    } elseif (strlen($new_pw) < 8) {
        $pw_error = 'New password must be at least 8 characters.';
    } elseif ($new_pw !== $confirm) {
        $pw_error = 'New passwords do not match.';
    } else {
        $new_hash    = password_hash($new_pw, PASSWORD_BCRYPT);
        $config_path = APP_ROOT . '/src/config.php';
        $config_src  = file_get_contents($config_path);
        $config_src  = str_replace(
            "define('PASSWORD_HASH', '" . PASSWORD_HASH . "');",
            "define('PASSWORD_HASH', '" . $new_hash . "');",
            $config_src
        );
        file_put_contents($config_path, $config_src);
        set_flash('success', 'Password updated successfully.');
        header('Location: ?page=settings&section=security');
        exit;
    }

    ob_start();
    ?>
    <div class="page-header"><h1>Settings</h1></div>
    <nav class="tab-nav">
        <?php
        $all_tabs = array_merge(
            ['statuses' => 'Statuses', 'squadrons' => 'Squadrons', 'pocs' => 'POCs',
             'courses' => 'Courses', 'software_tags' => 'Software Tags',
             'deployment_targets' => 'Deployment Targets', 'security' => 'Security'],
        );
        foreach ($all_tabs as $key => $label): ?>
        <a href="?page=settings&section=<?= $key ?>" class="tab <?= $section === $key ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="settings-section">
        <h2>Change Password</h2>
        <div class="flash flash-error"><?= e($pw_error) ?></div>
        <?php include __DIR__ . '/../templates/password_form.php'; ?>
    </div>
    <?php
    $content = ob_get_clean();
    $title   = 'Settings';
    require APP_ROOT . '/templates/layout.php';
    exit;
}

if ($section === 'security') {
    ob_start();
    ?>
    <div class="page-header"><h1>Settings</h1></div>
    <nav class="tab-nav">
        <?php
        $all_tabs = array_merge(
            ['statuses' => 'Statuses', 'squadrons' => 'Squadrons', 'pocs' => 'POCs',
             'courses' => 'Courses', 'software_tags' => 'Software Tags',
             'deployment_targets' => 'Deployment Targets', 'security' => 'Security'],
        );
        foreach ($all_tabs as $key => $label): ?>
        <a href="?page=settings&section=<?= $key ?>" class="tab <?= $section === $key ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="settings-section">
        <h2>Change Password</h2>
        <?php require APP_ROOT . '/templates/flash.php'; ?>
        <?php include __DIR__ . '/../templates/password_form.php'; ?>
    </div>
    <?php
    $content = ob_get_clean();
    $title   = 'Settings';
    require APP_ROOT . '/templates/layout.php';
    exit;
}

$configs = [
    'statuses'           => ['table' => 'statuses',           'fk_table' => 'projects',                   'fk_col' => 'status_id',   'label' => 'Statuses',           'has_sort' => true,  'is_poc' => false, 'has_squadron' => false, 'is_course' => false],
    'squadrons'          => ['table' => 'squadrons',          'fk_table' => 'projects',                   'fk_col' => 'squadron_id', 'label' => 'Squadrons',          'has_sort' => false, 'is_poc' => false, 'has_squadron' => false, 'is_course' => false],
    'pocs'               => ['table' => 'pocs',               'fk_table' => 'project_pocs',               'fk_col' => 'poc_id',      'label' => 'POCs',               'has_sort' => false, 'is_poc' => true,  'has_squadron' => false, 'is_course' => false],
    'courses'            => ['table' => 'courses',            'fk_table' => 'project_courses',            'fk_col' => 'course_id',   'label' => 'Courses',            'has_sort' => false, 'is_poc' => false, 'has_squadron' => true,  'is_course' => true],
    'software_tags'      => ['table' => 'software_tags',      'fk_table' => 'project_software_tags',      'fk_col' => 'tag_id',      'label' => 'Software Tags',      'has_sort' => false, 'is_poc' => false, 'has_squadron' => false, 'is_course' => false],
    'deployment_targets' => ['table' => 'deployment_targets', 'fk_table' => 'project_deployment_targets', 'fk_col' => 'target_id',   'label' => 'Deployment Targets', 'has_sort' => false, 'is_poc' => false, 'has_squadron' => false, 'is_course' => false],
];

$cfg = $configs[$section];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $item_id = (int)($_POST['id'] ?? 0);

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            set_flash('error', 'Name is required.');
        } else {
            try {
                if ($cfg['is_course']) {
                    $course_number = trim($_POST['course_number'] ?? '');
                    $squadron_id   = (int)($_POST['squadron_id'] ?? 0) ?: null;
                    db_execute("INSERT INTO {$cfg['table']} (name, course_number, squadron_id) VALUES (?, ?, ?)", [$name, $course_number, $squadron_id]);
                } elseif ($cfg['has_squadron'] && !$cfg['is_poc']) {
                    $squadron_id = (int)($_POST['squadron_id'] ?? 0) ?: null;
                    db_execute("INSERT INTO {$cfg['table']} (name, squadron_id) VALUES (?, ?)", [$name, $squadron_id]);
                } elseif ($cfg['is_poc']) {
                    $phone       = trim($_POST['phone']       ?? '');
                    $flight      = trim($_POST['flight']      ?? '');
                    $squadron_id = (int)($_POST['squadron_id'] ?? 0) ?: null;
                    db_execute("INSERT INTO pocs (name, phone, flight, squadron_id) VALUES (?, ?, ?, ?)", [$name, $phone, $flight, $squadron_id]);
                } elseif ($cfg['has_sort']) {
                    db_execute("INSERT INTO {$cfg['table']} (name, sort_order) VALUES (?, ?)", [$name, (int)($_POST['sort_order'] ?? 0)]);
                } else {
                    db_execute("INSERT INTO {$cfg['table']} (name) VALUES (?)", [$name]);
                }
                set_flash('success', 'Added.');
            } catch (Exception $e) {
                set_flash('error', 'Name already exists.');
            }
        }

    } elseif ($action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            set_flash('error', 'Name is required.');
        } else {
            try {
                if ($cfg['is_course']) {
                    $course_number = trim($_POST['course_number'] ?? '');
                    $squadron_id   = (int)($_POST['squadron_id'] ?? 0) ?: null;
                    db_execute("UPDATE {$cfg['table']} SET name=?, course_number=?, squadron_id=? WHERE id=?", [$name, $course_number, $squadron_id, $item_id]);
                } elseif ($cfg['has_squadron'] && !$cfg['is_poc']) {
                    $squadron_id = (int)($_POST['squadron_id'] ?? 0) ?: null;
                    db_execute("UPDATE {$cfg['table']} SET name=?, squadron_id=? WHERE id=?", [$name, $squadron_id, $item_id]);
                } elseif ($cfg['is_poc']) {
                    $phone       = trim($_POST['phone']       ?? '');
                    $flight      = trim($_POST['flight']      ?? '');
                    $squadron_id = (int)($_POST['squadron_id'] ?? 0) ?: null;
                    db_execute("UPDATE pocs SET name=?, phone=?, flight=?, squadron_id=? WHERE id=?", [$name, $phone, $flight, $squadron_id, $item_id]);
                } elseif ($cfg['has_sort']) {
                    db_execute("UPDATE {$cfg['table']} SET name=?, sort_order=? WHERE id=?", [$name, (int)($_POST['sort_order'] ?? 0), $item_id]);
                } else {
                    db_execute("UPDATE {$cfg['table']} SET name=? WHERE id=?", [$name, $item_id]);
                }
                set_flash('success', 'Updated.');
            } catch (Exception $e) {
                set_flash('error', 'Name already exists.');
            }
        }

    } elseif ($action === 'delete') {
        $in_use = db_query_one("SELECT COUNT(*) AS cnt FROM {$cfg['fk_table']} WHERE {$cfg['fk_col']} = ?", [$item_id]);
        if ($in_use && $in_use['cnt'] > 0) {
            set_flash('error', 'Cannot delete: this value is in use by ' . $in_use['cnt'] . ' project(s).');
        } else {
            // NULL out any soft references in project_requests before deleting
            $req_nulls = [
                'courses'            => 'course_id',
                'squadrons'          => 'squadron_id',
                'statuses'           => 'status_id',
            ];
            if (isset($req_nulls[$section])) {
                db_execute("UPDATE project_requests SET {$req_nulls[$section]} = NULL WHERE {$req_nulls[$section]} = ?", [$item_id]);
            }
            db_execute("DELETE FROM {$cfg['table']} WHERE id=?", [$item_id]);
            set_flash('success', 'Deleted.');
        }
    }

    header('Location: ?page=settings&section=' . $section);
    exit;
}

$order = $cfg['has_sort'] ? 'sort_order, name' : 'name';
if ($cfg['is_poc']) {
    $items = db_query("SELECT p.*, s.name AS squadron_name FROM pocs p LEFT JOIN squadrons s ON s.id = p.squadron_id ORDER BY p.name");
} elseif ($cfg['has_squadron']) {
    $items = db_query("SELECT c.*, s.name AS squadron_name FROM {$cfg['table']} c LEFT JOIN squadrons s ON s.id = c.squadron_id ORDER BY c.name");
} else {
    $items = db_query("SELECT * FROM {$cfg['table']} ORDER BY {$order}");
}

$all_squadrons = db_query('SELECT * FROM squadrons ORDER BY name');
$edit_id = (int)($_GET['edit'] ?? 0);

ob_start();
?>
<div class="page-header">
    <h1>Settings</h1>
</div>

<nav class="tab-nav">
    <?php foreach ($configs as $key => $c): ?>
    <a href="?page=settings&section=<?= $key ?>" class="tab <?= $section === $key ? 'active' : '' ?>"><?= e($c['label']) ?></a>
    <?php endforeach; ?>
    <a href="?page=settings&section=security" class="tab <?= $section === 'security' ? 'active' : '' ?>">Security</a>
</nav>

<div class="settings-section">
    <h2><?= e($cfg['label']) ?></h2>

    <?php if (empty($items)): ?>
        <p class="empty-state">No <?= strtolower(e($cfg['label'])) ?> yet.</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <?php if ($cfg['is_poc']): ?>
                    <th>Squadron</th><th>Flight</th><th>Phone</th>
                <?php elseif ($cfg['is_course']): ?>
                    <th>Course #</th><th>Squadron</th>
                <?php elseif ($cfg['has_squadron']): ?>
                    <th>Squadron</th>
                <?php elseif ($cfg['has_sort']): ?>
                    <th>Sort Order</th>
                <?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <?php $cols = $cfg['is_poc'] ? 4 : ($cfg['is_course'] ? 4 : ($cfg['has_squadron'] ? 3 : ($cfg['has_sort'] ? 3 : 2))); ?>
            <?php if ($edit_id === (int)$item['id']): ?>
            <tr>
                <td colspan="<?= $cols ?>">
                    <form method="post" action="?page=settings&section=<?= $section ?>" class="inline-edit-form">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <input type="text" name="name" value="<?= e($item['name']) ?>" placeholder="Course Name" required>
                        <?php if ($cfg['is_course']): ?>
                        <input type="text" name="course_number" value="<?= e($item['course_number'] ?? '') ?>" placeholder="Course #" style="max-width:160px">
                        <?php endif; ?>
                        <?php if ($cfg['has_squadron'] || $cfg['is_poc']): ?>
                        <select name="squadron_id" style="max-width:140px">
                            <option value="">— Squadron —</option>
                            <?php foreach ($all_squadrons as $sq): ?>
                            <option value="<?= $sq['id'] ?>" <?= $item['squadron_id'] == $sq['id'] ? 'selected' : '' ?>><?= e($sq['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        <?php if ($cfg['is_poc']): ?>
                        <input type="text" name="flight" value="<?= e($item['flight']) ?>" placeholder="Flight" style="max-width:90px">
                        <input type="text" name="phone"  value="<?= e($item['phone'])  ?>" placeholder="Phone"  style="max-width:130px">
                        <?php elseif ($cfg['has_sort']): ?>
                        <input type="number" name="sort_order" value="<?= (int)$item['sort_order'] ?>" style="width:70px">
                        <?php endif; ?>
                        <button type="submit">Save</button>
                        <a href="?page=settings&section=<?= $section ?>" class="btn-secondary">Cancel</a>
                    </form>
                </td>
            </tr>
            <?php else: ?>
            <tr>
                <td><?= e($item['name']) ?></td>
                <?php if ($cfg['is_poc']): ?>
                    <td><?= e($item['squadron_name'] ?? '—') ?></td>
                    <td><?= e($item['flight']) ?></td>
                    <td><?= e($item['phone'])  ?></td>
                <?php elseif ($cfg['is_course']): ?>
                    <td><?= e($item['course_number'] ?: '—') ?></td>
                    <td><?= e($item['squadron_name'] ?? '—') ?></td>
                <?php elseif ($cfg['has_squadron']): ?>
                    <td><?= e($item['squadron_name'] ?? '—') ?></td>
                <?php elseif ($cfg['has_sort']): ?>
                    <td><?= (int)$item['sort_order'] ?></td>
                <?php endif; ?>
                <td class="actions">
                    <a href="?page=settings&section=<?= $section ?>&edit=<?= $item['id'] ?>">Edit</a>
                    <form method="post" action="?page=settings&section=<?= $section ?>" style="display:inline"
                          onsubmit="return confirm('Delete \'<?= e(addslashes($item['name'])) ?>\'?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn-link danger-link">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h3>Add <?= e(rtrim($cfg['label'], 's')) ?></h3>
    <form method="post" action="?page=settings&section=<?= $section ?>" class="inline-add-form">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" placeholder="<?= $cfg['is_course'] ? 'Course Name' : 'Name' ?>" required>
        <?php if ($cfg['is_course']): ?>
        <input type="text" name="course_number" placeholder="Course #" style="max-width:160px">
        <?php endif; ?>
        <?php if ($cfg['has_squadron'] || $cfg['is_poc']): ?>
        <select name="squadron_id" style="max-width:140px">
            <option value="">— Squadron —</option>
            <?php foreach ($all_squadrons as $sq): ?>
            <option value="<?= $sq['id'] ?>"><?= e($sq['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if ($cfg['is_poc']): ?>
        <input type="text" name="flight" placeholder="Flight" style="max-width:90px">
        <input type="text" name="phone"  placeholder="Phone"  style="max-width:130px">
        <?php elseif ($cfg['has_sort']): ?>
        <input type="number" name="sort_order" placeholder="Sort" value="0" style="width:70px">
        <?php endif; ?>
        <button type="submit">Add</button>
    </form>
</div>
<?php

$content = ob_get_clean();
$title   = 'Settings';
require APP_ROOT . '/templates/layout.php';
