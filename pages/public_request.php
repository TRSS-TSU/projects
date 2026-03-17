<?php
// No auth required

$errors = [];
$vals = [
    'poc_name'         => '',
    'poc_squadron'     => '',
    'course_number'    => '',
    'course_name'      => '',
    'message'          => '',
    'potential_impact' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['poc_name']         = trim($_POST['poc_name']         ?? '');
    $vals['poc_squadron']     = trim($_POST['poc_squadron']     ?? '');
    $vals['course_number']    = trim($_POST['course_number']    ?? '');
    $vals['course_name']      = trim($_POST['course_name']      ?? '');
    $vals['message']          = trim($_POST['message']          ?? '');
    $vals['potential_impact'] = trim($_POST['potential_impact'] ?? '');

    if ($vals['poc_name'] === '')  $errors[] = 'Your name is required.';
    if ($vals['message'] === '')   $errors[] = 'Please describe your request or idea.';

    if (empty($errors)) {
        $db = get_db();
        db_execute(
            "INSERT INTO project_requests (poc_name, poc_squadron, course_number, course_name, message, potential_impact)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$vals['poc_name'], $vals['poc_squadron'], $vals['course_number'],
             $vals['course_name'], $vals['message'], $vals['potential_impact']]
        );
        header('Location: ?page=thankyou');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a Request — 81 TRSS Courseware Development</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="pub-header">
    <div class="pub-header-inner">
        <span class="pub-title"><a href="?page=landing" style="color:inherit;text-decoration:none">81 TRSS Courseware Development</a></span>
        <span class="pub-count">Project Request</span>
    </div>
</header>

<main class="pub-main" style="max-width:720px;margin:0 auto;padding:2rem 1rem 4rem">

    <div class="page-header" style="margin-bottom:1.5rem">
        <h1 style="font-size:1.6rem">Submit a Project Request</h1>
    </div>
    <p style="color:var(--text-muted);margin-bottom:2rem">
        Tell us about your training need or project idea. A developer will follow up within 2–3 business days to discuss next steps.
        Fields marked <span class="req">*</span> are required.
    </p>

    <?php if ($errors): ?>
    <div class="flash flash-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" action="?page=request" class="form-card">

        <div class="form-row">
            <label for="poc_name">Your Name <span class="req">*</span></label>
            <input type="text" id="poc_name" name="poc_name" value="<?= e($vals['poc_name']) ?>"
                   placeholder="e.g. SSgt Jane Smith" required>
        </div>

        <div class="form-row">
            <label for="poc_squadron">Your Squadron</label>
            <input type="text" id="poc_squadron" name="poc_squadron" value="<?= e($vals['poc_squadron']) ?>"
                   placeholder="e.g. 334 TRS">
        </div>

        <div class="form-row">
            <label for="course_number">Course Number</label>
            <input type="text" id="course_number" name="course_number" value="<?= e($vals['course_number']) ?>"
                   placeholder="e.g. J3AQR1A231-004">
        </div>

        <div class="form-row">
            <label for="course_name">Course Name</label>
            <input type="text" id="course_name" name="course_name" value="<?= e($vals['course_name']) ?>"
                   placeholder="e.g. Introduction to Radar Systems">
        </div>

        <div class="form-row">
            <label for="message">Request / Description <span class="req">*</span></label>
            <textarea id="message" name="message" rows="6" required
                      placeholder="Describe your training need, the problem you're trying to solve, or what you'd like to discuss in a meeting."><?= e($vals['message']) ?></textarea>
        </div>

        <div class="form-row">
            <label for="potential_impact">Potential Impact</label>
            <textarea id="potential_impact" name="potential_impact" rows="3"
                      placeholder="e.g. Approximately 400 students trained annually across 3 course sections. Currently requires 8 hours of instructor-led lab time that could be reduced with a simulation."><?= e($vals['potential_impact']) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit">Submit Request</button>
            <a href="?page=landing" class="btn-secondary">← Back</a>
        </div>

    </form>
</main>
</body>
</html>
