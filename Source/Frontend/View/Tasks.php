<?php
use App\Core\Me;
use App\Core\UUID;
use App\Enumeration\Priority;
use App\Enumeration\Role;
use App\Enumeration\WorkStatus;
use App\Middleware\Csrf;

if (!isset($project)) throw new Exception('Project ID is required');
if (!isset($tasks)) throw new Exception('Tasks data is required');

$projectData = [
    'name'      => htmlspecialchars($project->getName()),
    'publicId'  => UUID::toString($project->getPublicId()),
    'status'    => $project->getStatus()
];

$isAddable = Role::isProjectManager(Me::getInstance()->getRole()) && 
    $projectData['status'] !== WorkStatus::COMPLETED && 
    $projectData['status'] !== WorkStatus::CANCELLED;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">
    
    <title>Tasks</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="icon" type="image/x-icon" href="<?= IMAGE_PATH . 'logo-dark.ico' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Tasks.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'Sidenav.php' ?>

    <main class="task main-page flex-col">

        <!-- Search Bar -->
        <section class="search-bar-container">
            <?= searchBar([
                'Status' => [
                    WorkStatus::PENDING->getDisplayName(),
                    WorkStatus::ONGOING->getDisplayName(),
                    WorkStatus::COMPLETED->getDisplayName(),
                    WorkStatus::DELAYED->getDisplayName(),
                    WorkStatus::CANCELLED->getDisplayName()
                ],
                'Priority' => [
                    Priority::HIGH->getDisplayName(),
                    Priority::MEDIUM->getDisplayName(),
                    Priority::LOW->getDisplayName()
                ]
            ]) ?>
        </section>

        <!-- Task Grid -->
        <section class="task-grid-container" data-projectid="<?= $projectData['publicId'] ?>">
            <?php if ($tasks->count() === 0 && !$isAddable): ?>
                <div
                    class="no-tasks-wall no-content-wall <?= $tasks->count() > 0 ? 'no-display' : 'flex-col' ?>">
                    <img src="<?= ICON_PATH . 'empty_dw.svg' ?>" alt="No tasks available" title="No tasks available"
                        height="70">
                    <h3 class="center-text">No tasks available for this project.</h3>
                </div>
            <?php endif; ?>

            <section class="task-grid grid-card-container grid">
                <?php if ($isAddable): ?>
                    <a href="<?= REDIRECT_PATH . "add-task/" . $projectData['publicId'] ?>"
                        class="add-task-button task-grid-card grid-card flex-col flex-child-center-h flex-child-center-v">
                        <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add New Task" title="Add New Task" height="90">
                        <h3>Add New Task</h3>
                    </a>
                <?php endif; ?>

                <?php 
                    foreach ($tasks as $task) {
                        echo taskGridCard($task, $projectId);
                    } 
                ?>
            </section>

            <!-- Sentinel -->
            <div class="sentinel"></div>

        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'ToggleMenu.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'BreakTextFallback.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'Logout.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'Tasks' . DS . 'Search.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'Tasks' . DS . 'InfiniteScroll.js' ?>" defer></script>

</body>

</html>