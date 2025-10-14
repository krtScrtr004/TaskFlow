<?php
if (!$project)
    throw new Error('Project data is required.');
$projectId = $project->getPublicId();

if (!isset($tasks))
    throw new Error('Tasks data is required.');

$searchKey = isset($_GET['key']) ? htmlspecialchars($_GET['key']) : '';
$searchFilter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'tasks.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="task main-page flex-col">

        <!-- Search Bar -->
        <section>
            <?= searchBar([
                'Status' => [
                    'All Statuses',
                    WorkStatus::PENDING->getDisplayName(),
                    WorkStatus::ON_GOING->getDisplayName(),
                    WorkStatus::COMPLETED->getDisplayName(),
                    WorkStatus::DELAYED->getDisplayName(),
                    WorkStatus::CANCELLED->getDisplayName()
                ],
                'Priority' => [
                    'All Priorities',
                    TaskPriority::HIGH->getDisplayName(),
                    TaskPriority::MEDIUM->getDisplayName(),
                    TaskPriority::LOW->getDisplayName()
                ]
            ]) ?>
        </section>

        <!-- Task Grid -->
        <section class="task-grid-container" data-projectid="<?= $projectId ?>">

            <section class="task-grid grid">
                <?php if (Role::isProjectManager(Me::getInstance())): ?>
                    <a href="<?= REDIRECT_PATH . "add-task/$projectId" ?>"
                        class="add-task-button task-grid-card flex-col flex-child-center-h flex-child-center-v">
                        <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add New Task" title="Add New Task" height="90">
                        <h3>Add New Task</h3>
                    </a>
                <?php endif; ?>

                <?php foreach ($tasks as $task) {
                    echo taskGridCard($task, $projectId);
                } ?>
            </section>

            <!-- Sentinel -->
            <div class="sentinel"></div>

        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'break-text-fallback.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'tasks' . DS . 'search.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'tasks' . DS . 'infinite-scroll.js' ?>" defer></script>

</body>

</html>