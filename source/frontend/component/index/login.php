<form id="login_form" class="index-form flex-col" action="" method="POST">
    <?= hiddenCsrfInput() ?>

    <input type="text" id="login_email" name="login_email" min="<?= URI_MIN ?>" max="<?= URI_MAX ?>" placeholder="Please enter your email here"
        autocomplete="on" required>

    <div class="password-toggle-wrapper">
        <img class="absolute" src="<?= ICON_PATH . 'show_w.svg'; ?>" alt="Show password" title="Show password"
            width="18" height="18" />

        <input type="password" name="login_password" id="login_password" placeholder="Please enter your password here" min="<?= PASSWORD_MIN ?>"
            max="<?= PASSWORD_MAX ?>" required />
    </div>

    <button id="login_button" type="submit" class="blue-bg white-text">
        <div class="text-w-icon">
            <img src="<?= ICON_PATH . 'login_w.svg'; ?>" alt="Log In" title="Log In" height="20" />

            <h3>Log In</h3>
        </div>
    </button>
</form>