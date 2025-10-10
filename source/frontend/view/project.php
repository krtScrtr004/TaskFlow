<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Project</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'project.css' ?>">

</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="main-page">
        <?php 
        if (!isset($project)) {
            $createProject = '';

            if (Role::isProjectManager(Me::getInstance())) {
                // Only project managers can create projects
                $createProject = '<a href="' . REDIRECT_PATH . 'create-project" class="blue-text">Create Project</a>';
            }
            ?>
            <!-- No project -->
            <div class="no-project-wall no-content-wall full-body-content flex-col">
                <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No active project found" title="No active project found"
                    height="150">
                <h3>No active project found. <?= $createProject ?></h3>
            </div>
        <?php } else {
            require_once COMPONENT_PATH . 'project.php';
        } ?>
    </main>

    <?php if (isset($project)): ?>
        <script src="<?= PUBLIC_PATH . 'chart.umd.min.js' ?>"></script>

        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'progress-bar.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'task-chart.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'create-worker-card.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'terminate-worker.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'infinite-scroll-workers.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'cancel.js' ?>"></script>

        <script type="module" src="<?= EVENT_PATH . 'add-worker-modal' . DS . 'project' . DS . 'open.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'add-worker-modal' . DS . 'project' . DS . 'add.js' ?>"></script>
    <?php endif; ?>
</body>

</html>