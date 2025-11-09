<form id="reset_password_form" class="flex-col" action="" method="POST">
    <p class="description"><?= $component['description'] ?></p>

    <?= hiddenCsrfInput() ?>

    <input type="email" name="email" id="email" min="<?= URI_MIN ?>" max="<?= URI_MAX ?>" placeholder="Enter your email" required>

    <button id="send_link_button" type="button" class="blue-bg white-text">Send Reset Link</button>
</form>