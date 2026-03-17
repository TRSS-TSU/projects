<?php
// Shared fields for project create/edit forms.
// Requires: $vals, $statuses, $squadrons, $pocs, $courses, $tags, $targets
$squadron_is_other = ($vals['squadron_id'] ?? '') === 'other';
?>
<div class="form-row">
    <label for="project_number">Project Number <span class="req">*</span></label>
    <input type="text" id="project_number" name="project_number" value="<?= e($vals['project_number']) ?>" required>
</div>

<div class="form-row">
    <label for="project_name">Project Name <span class="req">*</span></label>
    <input type="text" id="project_name" name="project_name" value="<?= e($vals['project_name'] ?? '') ?>" required>
</div>

<div class="form-row">
    <label for="squadron_id">Squadron</label>
    <select id="squadron_id" name="squadron_id" onchange="toggleOther(this, 'squadron_other_wrap'); filterPocsBySquadron(this.value)">
        <option value="">— None —</option>
        <?php foreach ($squadrons as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($vals['squadron_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
        <option value="other" <?= $squadron_is_other ? 'selected' : '' ?>>Other…</option>
    </select>
    <div id="squadron_other_wrap" class="other-wrap" <?= $squadron_is_other ? '' : 'style="display:none"' ?>>
        <input type="text" name="squadron_other" placeholder="New squadron name…" value="<?= e($vals['squadron_other'] ?? '') ?>">
    </div>
</div>

<div class="form-row">
    <label>POC(s)</label>
    <div class="chip-group">
        <?php foreach ($pocs as $poc): ?>
        <label class="chip poc-chip" data-squadron="<?= (int)($poc['squadron_id'] ?? 0) ?>">
            <input type="checkbox" name="project_pocs[]" value="<?= $poc['id'] ?>"
                <?= in_array($poc['id'], $vals['project_pocs'] ?? []) ? 'checked' : '' ?>>
            <span>
                <?= e($poc['name']) ?>
                <?php if ($poc['flight']): ?><small class="chip-sub"><?= e($poc['flight']) ?></small><?php endif; ?>
            </span>
        </label>
        <?php endforeach; ?>
        <label class="chip chip-other">
            <input type="checkbox" name="project_pocs[]" value="other"
                onchange="toggleOther(this, 'poc_other_wrap')"
                <?= !empty($vals['poc_other_name']) ? 'checked' : '' ?>>
            <span>+ Other</span>
        </label>
    </div>
    <div id="poc_other_wrap" class="other-wrap poc-other-fields" <?= !empty($vals['poc_other_name']) ? '' : 'style="display:none"' ?>>
        <input type="text" name="poc_other_name"   placeholder="Name *"   value="<?= e($vals['poc_other_name']   ?? '') ?>">
        <input type="text" name="poc_other_phone"  placeholder="Phone"    value="<?= e($vals['poc_other_phone']  ?? '') ?>" style="max-width:140px">
        <input type="text" name="poc_other_flight" placeholder="Flight"   value="<?= e($vals['poc_other_flight'] ?? '') ?>" style="max-width:100px">
    </div>
</div>

<div class="form-row">
    <label for="description">Description <span class="req">*</span></label>
    <textarea id="description" name="description" rows="5" required><?= e($vals['description']) ?></textarea>
</div>

<div class="form-row">
    <label for="status_id">Status <span class="req">*</span></label>
    <select id="status_id" name="status_id" required>
        <option value="">— Select —</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $vals['status_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-row">
    <label for="start_date">Start Date <span class="req">*</span></label>
    <input type="date" id="start_date" name="start_date" value="<?= e($vals['start_date']) ?>" required>
</div>

<div class="form-row">
    <label for="completion_date">Completion Date</label>
    <input type="date" id="completion_date" name="completion_date" value="<?= e($vals['completion_date'] ?? '') ?>">
</div>

<?php if (!empty($courses)): ?>
<div class="form-row">
    <label>Courses</label>
    <div class="chip-group">
        <?php foreach ($courses as $c): ?>
        <label class="chip course-chip" data-squadron="<?= (int)($c['squadron_id'] ?? 0) ?>">
            <input type="checkbox" name="project_courses[]" value="<?= $c['id'] ?>"
                <?= in_array($c['id'], $vals['project_courses'] ?? []) ? 'checked' : '' ?>>
            <span>
                <?= e($c['name']) ?>
                <?php if ($c['course_number']): ?><small class="chip-sub"><?= e($c['course_number']) ?></small><?php endif; ?>
            </span>
        </label>
        <?php endforeach; ?>
        <label class="chip chip-other">
            <input type="checkbox" name="project_courses[]" value="other"
                onchange="toggleOther(this, 'course_other_wrap')"
                <?= !empty($vals['course_other']) ? 'checked' : '' ?>>
            <span>+ Other</span>
        </label>
    </div>
    <div id="course_other_wrap" class="other-wrap poc-other-fields" <?= !empty($vals['course_other']) ? '' : 'style="display:none"' ?>>
        <input type="text" name="course_other" placeholder="Course Name *" value="<?= e($vals['course_other'] ?? '') ?>">
        <input type="text" name="course_other_number" placeholder="Course #" value="<?= e($vals['course_other_number'] ?? '') ?>" style="max-width:180px">
    </div>
</div>
<?php endif; ?>

<div class="form-row">
    <label>Software Tags</label>
    <div class="chip-group">
        <?php foreach ($tags as $t): ?>
        <label class="chip">
            <input type="checkbox" name="software_tags[]" value="<?= $t['id'] ?>"
                <?= in_array($t['id'], $vals['software_tags'] ?? []) ? 'checked' : '' ?>>
            <span><?= e($t['name']) ?></span>
        </label>
        <?php endforeach; ?>
        <label class="chip chip-other">
            <input type="checkbox" name="software_tags[]" value="other"
                onchange="toggleOther(this, 'tag_other_wrap')"
                <?= !empty($vals['tag_other']) ? 'checked' : '' ?>>
            <span>+ Other</span>
        </label>
    </div>
    <div id="tag_other_wrap" class="other-wrap" <?= !empty($vals['tag_other']) ? '' : 'style="display:none"' ?>>
        <input type="text" name="tag_other" placeholder="New software tag…" value="<?= e($vals['tag_other'] ?? '') ?>">
    </div>
</div>

<div class="form-row">
    <label>Deployment Targets</label>
    <div class="chip-group">
        <?php foreach ($targets as $t): ?>
        <label class="chip">
            <input type="checkbox" name="deploy_targets[]" value="<?= $t['id'] ?>"
                <?= in_array($t['id'], $vals['deploy_targets'] ?? []) ? 'checked' : '' ?>>
            <span><?= e($t['name']) ?></span>
        </label>
        <?php endforeach; ?>
        <label class="chip chip-other">
            <input type="checkbox" name="deploy_targets[]" value="other"
                onchange="toggleOther(this, 'target_other_wrap')"
                <?= !empty($vals['target_other']) ? 'checked' : '' ?>>
            <span>+ Other</span>
        </label>
    </div>
    <div id="target_other_wrap" class="other-wrap" <?= !empty($vals['target_other']) ? '' : 'style="display:none"' ?>>
        <input type="text" name="target_other" placeholder="New deployment target…" value="<?= e($vals['target_other'] ?? '') ?>">
    </div>
</div>
