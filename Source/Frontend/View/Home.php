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
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Home.css' ?>">

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

    <script type="module" src="<?= EVENT_PATH . 'ToggleMenu.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'Logout.js' ?>" defer></script>
    <?php if (isset($project)): ?>
        <script src="<?= PUBLIC_PATH . 'chart.umd.min.js' ?>"></script>

        <script type="module" src="<?= EVENT_PATH . 'Home' . DS . 'TaskStatusChart.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'Home' . DS . 'ProgressBar.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'Home' . DS . 'TotalSpendingBar.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'Home' . DS . 'CreateWorkerCard.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'Home' . DS . 'SearchWorker.js' ?>" defer></script>
        <script type="module" src="<?= EVENT_PATH . 'Home' . DS . 'InfiniteScrollWorkers.js' ?>" defer></script>
        <?php if (Role::isProjectManager(Me::getInstance()->getRole())): ?>
            <script type="module" src="<?= EVENT_PATH . 'Home' . DS . 'RemoveTerminateWorker.js' ?>" defer></script>
            <script type="module" src="<?= EVENT_PATH . 'Home' . DS . 'Cancel.js' ?>" defer></script>
            <script type="module" src="<?= EVENT_PATH . 'AddWorkerModal' . DS . 'Project' . DS . 'Open.js' ?>" defer></script>
            <script type="module" src="<?= EVENT_PATH . 'AddWorkerModal' . DS . 'Project' . DS . 'Add.js' ?>" defer></script>
        <?php endif; ?>
    <?php endif; ?>
</body>

</html>