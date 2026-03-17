<?php
$flash = get_flash();
if ($flash): ?>
<div class="flash flash-<?= e($flash['type']) ?>">
    <?= e($flash['message']) ?>
</div>
<?php endif; ?>
