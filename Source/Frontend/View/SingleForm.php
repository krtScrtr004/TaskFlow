<?php
use App\Middleware\Csrf;
if (!$component) throw new Exception('Component is not defined');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

    <title><?= $component['title'] ?></title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="icon" type="image/x-icon" href="<?= IMAGE_PATH . 'logo-dark.ico' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'SingleForm.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'SingleForm' . DS . 'Project.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'SingleForm' . DS . 'Task.css' ?>">
</head>

<body class="single-form">
    <main class="full-body-content center-child">
        <div class="single-form-form form-wrapper flex-col">
            <div class="header-w-back">
                <!-- Form Title -->
                <h3><?= $component['title']; ?></h3>

                <!-- Back button -->
                <button type="button" class="back-button unset-button">
                    <img src="<?= ICON_PATH . 'back_w.svg' ?>" alt="Back" title="Back" height="24" width="24">
                </button>
            </div>

            <div> <?php require_once COMPONENT_PATH . $form ?>
            </div>
        </div>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'BackButton.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'Logout.js' ?>" defer></script>
    <?php if ($scripts): ?>
        <?php foreach ($scripts as $script):
            $scriptPath = EVENT_PATH . $script . '.js';
            ?>
            <script type="module" src="<?= $scriptPath ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>

</html>