<form method="post" action="?page=settings&section=security" class="inline-add-form" style="flex-direction:column;align-items:flex-start;gap:1rem;max-width:400px">
    <div class="form-row" style="width:100%">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="form-row" style="width:100%">
        <label for="new_password">New Password <span class="req">*</span></label>
        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
        <small style="color:var(--muted);margin-top:.3rem">Minimum 8 characters.</small>
    </div>
    <div class="form-row" style="width:100%">
        <label for="confirm_password">Confirm New Password <span class="req">*</span></label>
        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
    </div>
    <button type="submit">Update Password</button>
</form>
