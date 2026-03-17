// Toggle "Other" input visibility
function toggleOther(trigger, wrapId) {
    var wrap = document.getElementById(wrapId);
    if (!wrap) return;
    var isOther = trigger.tagName === 'SELECT'
        ? trigger.value === 'other'
        : trigger.checked;
    wrap.style.display = isOther ? 'block' : 'none';
    var input = wrap.querySelector('input[type="text"]');
    if (input) {
        input.required = isOther;
        if (!isOther) input.value = '';
    }
}

// Filter POC and Course chips by selected squadron
function filterPocsBySquadron(squadronId) {
    document.querySelectorAll('.poc-chip, .course-chip').forEach(function (chip) {
        var chipSquadron = chip.dataset.squadron || '0';
        // Show all when no squadron selected, or squadron_id is 0 (unassigned), or matches
        var show = !squadronId || chipSquadron === '0' || chipSquadron === String(squadronId);
        chip.style.display = show ? '' : 'none';
        // Uncheck hidden chips so they don't get submitted
        if (!show) {
            var cb = chip.querySelector('input[type="checkbox"]');
            if (cb) cb.checked = false;
        }
    });
}

// Run filter on page load if a squadron is already selected (edit form)
document.addEventListener('DOMContentLoaded', function () {
    var sq = document.getElementById('squadron_id');
    if (sq && sq.value) filterPocsBySquadron(sq.value);

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
