<?php

$db          = get_db();
$view_id     = (int)($_GET['id'] ?? 0);
$errors      = [];
$statuses    = db_query('SELECT * FROM statuses ORDER BY sort_order, name');
$squadrons   = db_query('SELECT * FROM squadrons ORDER BY name');
$all_courses = db_query('SELECT * FROM courses ORDER BY name');

// --- POST: update a request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $item_id = (int)($_POST['id'] ?? 0);

    if ($action === 'update') {
        $poc_name         = trim($_POST['poc_name']         ?? '');
        $poc_squadron     = trim($_POST['poc_squadron']     ?? '');
        $course_number    = trim($_POST['course_number']    ?? '');
        $course_name      = trim($_POST['course_name']      ?? '');
        $message          = trim($_POST['message']          ?? '');
        $potential_impact = trim($_POST['potential_impact'] ?? '');
        $status           = trim($_POST['status']           ?? 'pending');
        $status_id        = (int)($_POST['status_id']       ?? 0) ?: null;

        // Resolve squadron
        $raw_squadron  = trim($_POST['req_squadron_id'] ?? '');
        $squadron_other = trim($_POST['req_squadron_other'] ?? '');
        $squadron_id   = null;
        if ($raw_squadron === 'other' && $squadron_other !== '') {
            $squadron_id = db_execute('INSERT OR IGNORE INTO squadrons (name) VALUES (?)', [$squadron_other]);
            if (!$squadron_id) $squadron_id = db_query_one('SELECT id FROM squadrons WHERE name = ?', [$squadron_other])['id'];
        } elseif (is_numeric($raw_squadron) && (int)$raw_squadron > 0) {
            $squadron_id = (int)$raw_squadron;
        }

        // Resolve course
        $raw_course        = trim($_POST['req_course_id']      ?? '');
        $course_other_name = trim($_POST['req_course_other_name']   ?? '');
        $course_other_num  = trim($_POST['req_course_other_number'] ?? '');
        $course_id         = null;
        if ($raw_course === 'other' && $course_other_name !== '') {
            $course_id = db_execute('INSERT OR IGNORE INTO courses (name, course_number, squadron_id) VALUES (?, ?, ?)',
                [$course_other_name, $course_other_num, $squadron_id]);
            if (!$course_id) $course_id = db_query_one('SELECT id FROM courses WHERE name = ?', [$course_other_name])['id'];
            // Refresh course list after potential insert
            $all_courses = db_query('SELECT * FROM courses ORDER BY name');
        } elseif (is_numeric($raw_course) && (int)$raw_course > 0) {
            $course_id = (int)$raw_course;
        }

        $allowed_statuses = ['pending', 'reviewed', 'converted'];
        if (!in_array($status, $allowed_statuses, true)) $status = 'pending';

        if ($poc_name === '') {
            $errors[] = 'POC Name is required.';
        } else {
            db_execute(
                "UPDATE project_requests
                 SET poc_name=?, poc_squadron=?, course_number=?, course_name=?,
                     message=?, potential_impact=?, status=?, status_id=?,
                     squadron_id=?, course_id=?, updated_at=CURRENT_TIMESTAMP
                 WHERE id=?",
                [$poc_name, $poc_squadron, $course_number, $course_name,
                 $message, $potential_impact, $status, $status_id,
                 $squadron_id, $course_id, $item_id]
            );
            set_flash('success', 'Request updated.');
            header('Location: ?page=requests&id=' . $item_id);
            exit;
        }

    } elseif ($action === 'delete') {
        db_execute('DELETE FROM project_requests WHERE id=?', [$item_id]);
        set_flash('success', 'Request deleted.');
        header('Location: ?page=requests');
        exit;
    }
}

// --- Single request view/edit ---
if ($view_id) {
    $request = db_query_one(
        'SELECT r.*, sq.name AS squadron_name, c.name AS course_db_name, c.course_number AS course_db_number,
                s.name AS status_name
         FROM project_requests r
         LEFT JOIN squadrons sq ON sq.id = r.squadron_id
         LEFT JOIN courses c    ON c.id  = r.course_id
         LEFT JOIN statuses s   ON s.id  = r.status_id
         WHERE r.id = ?',
        [$view_id]
    );
    if (!$request) {
        set_flash('error', 'Request not found.');
        header('Location: ?page=requests');
        exit;
    }

    // Repopulate from POST on validation error
    if (!empty($errors)) {
        $request['poc_name']         = $_POST['poc_name']         ?? $request['poc_name'];
        $request['poc_squadron']     = $_POST['poc_squadron']     ?? $request['poc_squadron'];
        $request['course_number']    = $_POST['course_number']    ?? $request['course_number'];
        $request['course_name']      = $_POST['course_name']      ?? $request['course_name'];
        $request['message']          = $_POST['message']          ?? $request['message'];
        $request['potential_impact'] = $_POST['potential_impact'] ?? $request['potential_impact'];
        $request['status']           = $_POST['status']           ?? $request['status'];
        $request['status_id']        = $_POST['status_id']        ?? $request['status_id'];
        $request['squadron_id']      = $_POST['req_squadron_id']  ?? $request['squadron_id'];
        $request['course_id']        = $_POST['req_course_id']    ?? $request['course_id'];
    }

    $sq_is_other     = ($request['squadron_id'] === 'other');
    $course_is_other = ($request['course_id']   === 'other');

    ob_start();
    ?>
    <div class="page-header">
        <h1>Request #<?= $request['id'] ?></h1>
        <a href="?page=requests" class="btn-secondary">← All Requests</a>
    </div>

    <?php if ($errors): ?>
    <div class="flash flash-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <div class="form-card" style="max-width:800px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap">
            <span style="color:var(--muted);font-size:.85rem">Submitted: <?= format_ts($request['created_at']) ?></span>
            <a href="?page=project&action=new&req=<?= $request['id'] ?>" class="btn">+ Create Project from Request</a>
        </div>

        <form method="post" action="?page=requests&id=<?= $request['id'] ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $request['id'] ?>">

            <!-- Workflow status (internal) -->
            <div class="form-row">
                <label for="req_workflow_status">Workflow Status</label>
                <select id="req_workflow_status" name="status">
                    <option value="pending"   <?= $request['status'] === 'pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="reviewed"  <?= $request['status'] === 'reviewed'  ? 'selected' : '' ?>>Reviewed</option>
                    <option value="converted" <?= $request['status'] === 'converted' ? 'selected' : '' ?>>Converted to Project</option>
                </select>
            </div>

            <!-- Project status from Settings -->
            <div class="form-row">
                <label for="req_status_id">Project Status</label>
                <select id="req_status_id" name="status_id">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($statuses as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $request['status_id'] == $st['id'] ? 'selected' : '' ?>><?= e($st['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="req_poc_name">POC Name <span class="req">*</span></label>
                <input type="text" id="req_poc_name" name="poc_name" value="<?= e($request['poc_name']) ?>" required>
            </div>

            <!-- Customer-submitted squadron text (read-only context) -->
            <?php if ($request['poc_squadron']): ?>
            <div class="form-row">
                <label>Submitted Squadron</label>
                <span style="padding:.45rem 0;color:var(--muted);font-size:.9rem"><?= e($request['poc_squadron']) ?></span>
            </div>
            <?php endif; ?>

            <!-- Developer-mapped squadron (DB dropdown) -->
            <div class="form-row">
                <label for="req_squadron_id">Squadron <small style="color:var(--muted)">(mapped)</small></label>
                <select id="req_squadron_id" name="req_squadron_id"
                        onchange="toggleOther(this,'req_squadron_other_wrap'); filterPocsBySquadron(this.value)">
                    <option value="">— None —</option>
                    <?php foreach ($squadrons as $sq): ?>
                    <option value="<?= $sq['id'] ?>" <?= $request['squadron_id'] == $sq['id'] ? 'selected' : '' ?>><?= e($sq['name']) ?></option>
                    <?php endforeach; ?>
                    <option value="other" <?= $sq_is_other ? 'selected' : '' ?>>Other…</option>
                </select>
                <div id="req_squadron_other_wrap" class="other-wrap" <?= $sq_is_other ? '' : 'style="display:none"' ?>>
                    <input type="text" name="req_squadron_other" placeholder="New squadron name…">
                </div>
            </div>

            <!-- Customer-submitted course text (read-only context) -->
            <?php if ($request['course_number'] || $request['course_name']): ?>
            <div class="form-row">
                <label>Submitted Course</label>
                <span style="padding:.45rem 0;color:var(--muted);font-size:.9rem">
                    <?php if ($request['course_number']): ?><strong><?= e($request['course_number']) ?></strong> — <?php endif; ?>
                    <?= e($request['course_name'] ?: '—') ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Developer-mapped course (chips filtered by squadron) -->
            <div class="form-row">
                <label>Course <small style="color:var(--muted)">(mapped)</small></label>
                <div class="chip-group">
                    <?php foreach ($all_courses as $c): ?>
                    <label class="chip course-chip" data-squadron="<?= (int)($c['squadron_id'] ?? 0) ?>">
                        <input type="radio" name="req_course_id" value="<?= $c['id'] ?>"
                            <?= $request['course_id'] == $c['id'] ? 'checked' : '' ?>>
                        <span>
                            <?= e($c['name']) ?>
                            <?php if ($c['course_number']): ?><small class="chip-sub"><?= e($c['course_number']) ?></small><?php endif; ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                    <label class="chip chip-other">
                        <input type="radio" name="req_course_id" value="other"
                            onchange="toggleOther(this,'req_course_other_wrap')"
                            <?= $course_is_other ? 'checked' : '' ?>>
                        <span>+ Other</span>
                    </label>
                </div>
                <div id="req_course_other_wrap" class="other-wrap poc-other-fields" <?= $course_is_other ? '' : 'style="display:none"' ?>>
                    <input type="text" name="req_course_other_name"   placeholder="Course Name *">
                    <input type="text" name="req_course_other_number" placeholder="Course #" style="max-width:180px">
                </div>
            </div>

            <div class="form-row">
                <label for="req_message">Request / Description</label>
                <textarea id="req_message" name="message" rows="6"><?= e($request['message']) ?></textarea>
            </div>

            <div class="form-row">
                <label for="req_impact">Potential Impact</label>
                <textarea id="req_impact" name="potential_impact" rows="3"><?= e($request['potential_impact']) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit">Save Changes</button>
                <a href="?page=requests" class="btn-secondary">Cancel</a>
            </div>
        </form>

        <form method="post" action="?page=requests&id=<?= $request['id'] ?>" style="margin-top:1rem"
              onsubmit="return confirm('Delete this request permanently?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $request['id'] ?>">
            <button type="submit" class="btn-link danger-link">Delete Request</button>
        </form>
    </div>

    <script>
    // Run course filter on load if a squadron is already mapped
    document.addEventListener('DOMContentLoaded', function () {
        var sq = document.getElementById('req_squadron_id');
        if (sq && sq.value && sq.value !== 'other') filterPocsBySquadron(sq.value);
    });
    </script>
    <?php
    $content = ob_get_clean();
    $title   = 'Request #' . $request['id'];
    require APP_ROOT . '/templates/layout.php';
    return;
}

// --- List view ---
$filter_status = $_GET['status'] ?? '';
$allowed_filter = ['', 'pending', 'reviewed', 'converted'];
if (!in_array($filter_status, $allowed_filter, true)) $filter_status = '';

if ($filter_status) {
    $requests = db_query('SELECT * FROM project_requests WHERE status = ? ORDER BY created_at DESC', [$filter_status]);
} else {
    $requests = db_query('SELECT * FROM project_requests ORDER BY created_at DESC');
}

$counts = [
    'all'       => db_query_one('SELECT COUNT(*) AS n FROM project_requests')['n'],
    'pending'   => db_query_one("SELECT COUNT(*) AS n FROM project_requests WHERE status='pending'")['n'],
    'reviewed'  => db_query_one("SELECT COUNT(*) AS n FROM project_requests WHERE status='reviewed'")['n'],
    'converted' => db_query_one("SELECT COUNT(*) AS n FROM project_requests WHERE status='converted'")['n'],
];

ob_start();
?>
<div class="page-header">
    <h1>Project Requests</h1>
    <a href="?page=landing" target="_blank" class="btn-secondary">View Public Landing Page</a>
</div>

<nav class="tab-nav" style="margin-bottom:1.5rem">
    <a href="?page=requests" class="tab <?= $filter_status === '' ? 'active' : '' ?>">All (<?= $counts['all'] ?>)</a>
    <a href="?page=requests&status=pending"   class="tab <?= $filter_status === 'pending'   ? 'active' : '' ?>">Pending (<?= $counts['pending'] ?>)</a>
    <a href="?page=requests&status=reviewed"  class="tab <?= $filter_status === 'reviewed'  ? 'active' : '' ?>">Reviewed (<?= $counts['reviewed'] ?>)</a>
    <a href="?page=requests&status=converted" class="tab <?= $filter_status === 'converted' ? 'active' : '' ?>">Converted (<?= $counts['converted'] ?>)</a>
</nav>

<?php if (empty($requests)): ?>
    <p class="empty-state">No requests<?= $filter_status ? ' with status "' . e($filter_status) . '"' : '' ?> yet.</p>
<?php else: ?>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Submitted</th>
            <th>POC Name</th>
            <th>Squadron</th>
            <th>Course</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $req): ?>
    <tr>
        <td><?= $req['id'] ?></td>
        <td style="white-space:nowrap"><?= format_ts($req['created_at']) ?></td>
        <td><?= e($req['poc_name']) ?></td>
        <td><?= e($req['poc_squadron'] ?: '—') ?></td>
        <td>
            <?php if ($req['course_number']): ?>
                <span style="color:var(--muted);font-size:.8rem"><?= e($req['course_number']) ?></span><br>
            <?php endif; ?>
            <?= e($req['course_name'] ?: '—') ?>
        </td>
        <td>
            <?php
            $badge_class = match($req['status']) {
                'pending'   => 'status-badge status-pending',
                'reviewed'  => 'status-badge status-reviewed',
                'converted' => 'status-badge status-converted',
                default     => 'status-badge',
            };
            ?>
            <span class="<?= $badge_class ?>"><?= ucfirst(e($req['status'])) ?></span>
        </td>
        <td class="actions">
            <a href="?page=requests&id=<?= $req['id'] ?>">Review</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php

$content = ob_get_clean();
$title   = 'Project Requests';
require APP_ROOT . '/templates/layout.php';
