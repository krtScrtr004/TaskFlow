<?php use App\Middleware\Csrf; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">
    
    <title>Confirm Email</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="icon" type="image/x-icon" href="<?= IMAGE_PATH . 'logo-dark.ico' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'index.css' ?>">
</head>

<body>
    <main class="confirm-email flex-col center-child">
        <img src="<?= IMAGE_PATH . 'logo-light.svg' ?>" alt="TaskFlow" title="TaskFlow" height="200">
        <h1>Email has been <span class="green-text">successfully confirmed!</span></h1>
        <p>You may now <a class="blue-text" href="<?= REDIRECT_PATH . 'login' ?>">log in to your account</a></p>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'register' . DS . 'confirm-email.js' ?>" defer></script>
</body>
</html>