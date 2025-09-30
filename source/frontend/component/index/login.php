<form id="login_form" class="index-form flex-col" action="" method="POST">
    <input
        type="text"
        id="email"
        name="email"
        min="8"
        max="255"
        placeholder="Please enter your email here"
        autocomplete="on"
        required>

    <div class="password-toggle-wrapper">
        <img
            class="absolute"
            src="<?= ICON_PATH . 'show_b.svg'; ?>"
            alt="Show password"
            title="Show password"
            width="18" height="18" />

        <input
            type="password"
            name="password"
            id="password"
            placeholder="Please enter your password here"
            min="8"
            max="255"
            required />
    </div>

    <button id="login_button" type="button" class="blue-bg white-text">
        Log In
    </button>
</form>