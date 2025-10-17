<?php if (!$component)
    throw new Error('Component is not defined') ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $component['title'] ?></title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'index.css' ?>">

</head>

<body class="index">
    <main class="flex-row">
        <section class="banner flex-col flex-child-center-h flex-child-center-v black-bg">
            <img src="<?= IMAGE_PATH . 'logo-light.svg' ?>" alt="TaskFlow" title="TaskFlow" height="200">
            <h1 class="white-text center-text">IT Project Management Platform</h1>
        </section>

        <section class="form flex-col flex-child-center-v">
            <h1>
                <?= $component['title'] ?>
            </h1>

            <hr class="line-divider">

            <div class="called-form-container">
                <?php include_once COMPONENT_PATH . 'index/' . $component['form'] . '.php' ?>
            </div>

            <div class="index-redirect">
                <?php if (strcasecmp($page, 'login') === 0): ?>
                    <p class="center-text">
                        <a href="<?= REDIRECT_PATH . 'reset-password' ?>">Forget Password</a>
                    </p>
                    <p class="center-text">Don't have an account?
                        <a class="blue-text" href="<?= REDIRECT_PATH . 'register' ?>">Sign up</a>
                    </p>
                <?php elseif (strcasecmp($page, 'register') === 0): ?>
                    <p class="center-text">Already have an account?
                        <a class="blue-text" href="<?= REDIRECT_PATH . 'login' ?>">Log in</a>
                    <?php endif ?>
            </div>
        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'toggle-password.js' ?>" defer></script>

    <?php if ($scripts): ?>
        <?php foreach ($scripts as $script):
            $scriptPath = EVENT_PATH . $script . '.js';
            ?>
            <script type="module" src="<?= $scriptPath ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>

</html>