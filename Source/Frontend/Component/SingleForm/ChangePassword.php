<form id="change_password_form" class="flex-col" action="" method="POST">
    <p class="center-text"><?= $component['description']; ?></p>

    <input class="validate-password" type="text" name="password" id="password" placeholder="Enter your password here"
        min="<?= PASSWORD_MIN ?>" max="<?= PASSWORD_MAX ?>" required>

    <!-- Password validator guide -->
    <ul class="flex-col password-list-validator">
        <li id="lower_case">At least one lowercase character</li>
        <li id="upper_case">At least one uppercase character</li>
        <li id="count">8 to 255 characters</li>
        <li id="characters">Only letters, numbers, and common punctuation (! @ ' . -) can be used</li>
    </ul>

    <button id="change_password_button" class="blue-bg white-text" type="submit">SUBMIT</button>
</form>