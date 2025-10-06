<?php 
if (!$project) 
    throw new Error('Project data is required.');
$projectId = $project->getPublicId();

if (!isset($tasks))
    throw new Error('Tasks data is required.'); 
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

    <link rel="stylesheet" href="<?= STYLE_PATH . 'task.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="task main-page flex-col">

        <!-- Search Bar -->
        <section>
            <form class="search-bar" action="" method="POST">
                <div>
                    <input type="text" name="search_task" id="search_task" placeholder="Search by Name or ID" min="1"
                        max="255" autocomplete="on" required>
                    <button id="search_task_button" type="button" class="transparent-bg">
                        <img src="<?= ICON_PATH . 'search_w.svg' ?>" alt="Search Task" title="Search Task" height="20">
                    </button>
                </div>

                <select class="" name="search_task_filter" id="search_task_filter">

                    <!-- Default Option -->
                    <option value="all" selected>All Tasks</option>

                    <!-- Filter By Status -->
                    <optgroup class="filter-group" label="Filter by Status">
                        <option value="allStatus">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="onGoing">On Going</option>
                        <option value="completed">Completed</option>
                        <option value="delayed">Delayed</option>
                        <option value="cancelled">Cancelled</option>
                    </optgroup>

                    <!-- Filter By Priority -->
                    <optgroup class="filter-group" label="Filter by Priority">
                        <option value="allPriority">All Priorities</option>
                        <option value="high">High Priority</option>
                        <option value="medium">Medium Priority</option>
                        <option value="low">Low Priority</option>
                    </optgroup>

                </select>

            </form>
        </section>


        <!-- Task Grid -->
        <section class="task-grid-container">

            <section class="task-grid grid">
                <?php if (Role::isProjectManager(Me::getInstance())): ?>
                    <a href="<?=REDIRECT_PATH . "add-task/$projectId" ?>" class="task-grid-card flex-col flex-child-center-h flex-child-center-v">
                        <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add New Task" title="Add New Task" height="90">
                        <h3>Add New Task</h3>
                    </a>
                <?php endif; ?>

                <?php
                foreach ($tasks as $task):
                    $taskDetails = [
                        'id' => htmlspecialchars($task->getPublicId()),
                        'name' => htmlspecialchars($task->getName()),
                        'description' => htmlspecialchars($task->getDescription()),
                        'startDateTime' => htmlspecialchars(formatDateTime($task->getStartDateTime(), 'Y-m-d')),
                        'completionDateTime' => htmlspecialchars(formatDateTime($task->getCompletionDateTime(), 'Y-m-d')),
                        'status' => $task->getStatus(),
                        'priority' => $task->getPriority()
                    ];
                    ?>
                    <div class="task-grid-card">
                        <a class="flex-col full-body-content" href="">
                            <section>
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Task" title="Task" height="24">
                                    <h3 class="task-name"><?= $taskDetails['name'] ?></h3>
                                </div>
                                <p class="task-id"><em><?= $taskDetails['id'] ?></em></p>
                            </section>

                            <!-- Task Description -->
                            <p class="task-description multi-line-ellipsis" title="<?= $taskDetails['description'] ?>">
                                <?= $taskDetails['description'] ?>
                            </p>

                            <!-- Task Schedule -->
                            <section class="task-schedule flex-col">
                                <!-- Start Date -->
                                <div class="flex-row">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date"
                                            height="20">
                                        <p>Start: </p>
                                    </div>

                                    <p><strong><?= $taskDetails['startDateTime'] ?></strong></p>
                                </div>

                                <!-- Completion Date -->
                                <div class="flex-row">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completion Date"
                                            title="Completion Date" height="20">
                                        <p>End: </p>
                                    </div>

                                    <p><strong><?= $taskDetails['completionDateTime'] ?></strong></p>
                            </section>

                            <section class="task-badge flex-row flex-child-end-h">
                                <?php
                                echo TaskPriority::badge($taskDetails['priority']);
                                echo WorkStatus::badge($taskDetails['status'])
                                    ?>
                            </section>

                        </a>
                    </div>
                <?php endforeach; ?>
            </section>

        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'break-text-fallback.js' ?>" defer></script>
</body>

</html>