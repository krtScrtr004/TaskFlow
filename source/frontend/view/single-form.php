<?php
if (!$component) {
    throw new Error('Component is not defined');
}
?>

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

    <link rel="stylesheet" href="<?= STYLE_PATH . 'single-form.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'edit-project.css' ?>">
</head>

<body class="single-form">
    <main class="full-body-content center-child">
        <div class="single-form-form form-wrapper flex-col">
            <div class="header-w-back">
                <!-- Form Title -->
                <h3><?= $component['title']; ?></h3>

                <!-- Back button -->
                <button type="button" class="back-button unset-button">
                    <img src="<?= ICON_PATH . 'back.svg' ?>" alt="Back" title="Back" height="24" width="24">
                </button>
            </div>

            <div> <?php require_once COMPONENT_PATH . $form ?>
            </div>
        </div>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'back-button.js' ?>" defer></script>
    <?php if ($scripts): ?>
        <?php foreach ($scripts as $script):
            $scriptPath = EVENT_PATH . $script . '.js';
            ?>
            <script type="module" src="<?= $scriptPath ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>

</html>