<?php
use App\Core\Me;
use App\Enumeration\Role;
use App\Middleware\Csrf;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

    <title>Home</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="icon" type="image/x-icon" href="<?= IMAGE_PATH . 'logo-dark.ico' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'home.css' ?>">

</head>

<body>
    <?php require_once COMPONENT_PATH . 'Sidenav.php' ?>

    <main class="main-page">
        <?php
        if (!isset($project)) {
            $createProject = '';

            if (Role::isProjectManager(Me::getInstance()->getRole())) {
                // Only project managers can create projects
                $createProject = '<a href="' . REDIRECT_PATH . 'create-project" class="blue-text">Create Project</a>';
            }
            ?>
            <!-- No project -->
            <div class="no-project-wall no-content-wall light-black-bg full-body-content flex-col">
                <img src="<?= ICON_PATH . 'empty_dw.svg' ?>" alt="No active project found" title="No active project found"
                    height="150">
                <p>No active project found. <?= $createProject ?></p>
            </div>
        <?php } else {
            require_once COMPONENT_PATH . 'Project.php';
        } ?>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'toggle-menu.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'logout.js' ?>" defer></script>
    <?php if (isset($project)): ?>
        <script src="<?= PUBLIC_PATH . 'chart.umd.min.js' ?>"></script>

        <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'task-status-chart.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'progress-bar.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'total-spending-bar.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'create-worker-card.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'search-worker.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'infinite-scroll-workers.js' ?>" defer></script>
        <?php if (Role::isProjectManager(Me::getInstance()->getRole())): ?>
            <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'remove-terminate-worker.js' ?>" defer></script>
            <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'cancel.js' ?>" defer></script>
            <script type="module" src="<?= EVENT_PATH . 'add-worker-modal' . DS . 'project' . DS . 'open.js' ?>" defer></script>
            <script type="module" src="<?= EVENT_PATH . 'add-worker-modal' . DS . 'project' . DS . 'add.js' ?>" defer></script>
        <?php endif; ?>
    <?php endif; ?>
</body>

</html>