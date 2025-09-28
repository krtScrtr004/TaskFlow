<form id="forget_password_form" class="flex-col" action="" method="POST">
    <p class="description"><?= $component['description'] ?></p>

    <input type="email" name="email" id="email" placeholder="Enter your email" required>

    <button type="button" class="blue-bg white-text">Send Reset Link</button>
</form>