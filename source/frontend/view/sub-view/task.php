<?php

use App\Core\Me;
use App\Core\UUID;
use App\Entity\Project;
use App\Entity\Task;
use App\Enumeration\Role;
use App\Enumeration\WorkStatus;
use App\Enumeration\TaskPriority;
use App\Exception\NotFoundException;

if (!isset($project) || !$project instanceof Project) {
    throw new NotFoundException('Project is not defined.');
}

if (!isset($task) || !$task instanceof Task) {
    throw new NotFoundException('Task is not defined.');
}

$projectData = [
    'id' => htmlspecialchars(UUID::toString($project->getPublicId()))
];

$taskData = [
    'id'                    => htmlspecialchars(UUID::toString($task->getPublicId())),
    'name'                  => htmlspecialchars($task->getName()),
    'description'           => htmlspecialchars($task->getDescription()),
    'workers'               => $task->getWorkers()->getAssigned(),
    'startDateTime'         => $task->getStartDateTime(),
    'completionDateTime'    => $task->getCompletionDateTime(),
    'status'                => $task->getStatus(),
    'priority'              => $task->getPriority(),
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $taskData['name'] ?? 'Task' ?></title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'tasks.css' ?>">
</head>

<body>
    <?php
    require_once COMPONENT_PATH . 'sidenav.php';
    require_once COMPONENT_PATH . 'template/edit-task-modal.php';
    require_once COMPONENT_PATH . 'template/user-info-card.php';
    require_once COMPONENT_PATH . 'template/add-worker-modal.php';
    ?>

    <main class="view-task-info main-page flex-col" data-projectid="<?= $projectData['id'] ?>"
        data-taskid="<?= $taskData['id'] ?>">

        <!-- Task Info -->
        <section class="task-info content-section-block flex-col">

            <!-- Task Name and Status -->
            <div class="main flex-row">
                <div class="heading text-w-icon">
                    <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="<?= $taskData['name'] ?>"
                        title="<?= $taskData['name'] ?>" height="24">

                    <h3 class="task-name wrap-text">
                        <?= $taskData['name'] ?>
                    </h3>
                </div>

                <?= WorkStatus::badge($taskData['status']) ?>
            </div>

            <p class="task-id"><em><?= $taskData['id'] ?></em></p>

            <!-- Task Description -->
            <p class="task-description wrap-text"><?= $taskData['description'] ?></p>

            <!-- Task Schedule -->
            <div class="task-schedule flex-col">
                <!-- Task Start Date -->
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Task Start Date" title="Task Start Date"
                        height="16">

                    <p>Start Date: <?= htmlspecialchars(dateToWords($taskData['startDateTime'])) ?></p>
                </div>

                <!-- Task Completion Date -->
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Task Completion Date"
                        title="Task Completion Date" height="16">

                    <p>Completion Date: <?= htmlspecialchars(dateToWords($taskData['completionDateTime'])) ?></p>
                </div>

            </div>

            <!-- Task Priority -->
            <div class="task-priority">
                <?= TaskPriority::badge($taskData['priority']) ?>
            </div>


            <!-- Buttons -->
            <section class="action-buttons flex-row flex-child-end-v">
                <!-- Complete Task Button -->
                <button id="complete_task_button" type="button" class="green-bg">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Complete Task" title="Complete Task" height="20">
                        <h3>Complete</h3>
                    </div>
                </button>

                <!-- Edit Task Button -->
                <button id="edit_task_button" type="button" class="blue-bg">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'edit_w.svg' ?>" alt="Edit Task" title="Edit Task" height="20">
                        <h3>Edit</h3>
                    </div>
                </button>

                <!-- Cancel Button -->
                <button id="cancel_task_button" type="button" class="red-bg">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'delete_w.svg' ?>" alt="Cancel Task" title="Cancel Task" height="20">
                        <h3>Cancel</h3>
                    </div>
                </button>
            </section>

        </section>

        <!-- Assigned Workers -->
        <section class="assigned-workers content-section-block flex-col">

            <!-- Heading -->
            <div class="heading text-w-icon">
                <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Assigned Workers" title="Assigned Workers"
                    height="24">

                <h3 class="task-name wrap-text">
                    Assigned Workers
                </h3>
            </div>

            <!-- No Workers Wall -->
            <div
                class="no-workers-wall no-content-wall <?= count($taskData['workers']) > 0 ? 'no-display' : 'flex-col' ?>">
                <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No workers assigned" title="No workers assigned"
                    height="100">
                <h3>No workers assigned to this task.</h3>
            </div>

            <!-- Worker Grid Cards -->
            <section class="worker-grid grid">
                <?php foreach ($taskData['workers'] as $worker) {
                    echo workerGridCard($worker);
                } ?>
            </section>

            <?php if (Role::isProjectManager(Me::getInstance())): ?>
                <!-- Add Worker Button -->
                <button id="add_worker_button" type="button" class="transparent-bg">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Worker" title="Add Worker" height="20">
                        <h3>Add Worker</h3>
                    </div>
                </button>
            <?php endif; ?>

        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'logout.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'tasks' . DS . 'create-worker-card.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'tasks' . DS . 'terminate-worker.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'add-worker-modal' . DS . 'task' . DS . 'existing' . DS . 'open.js' ?>"
        defer></script>
    <script type="module" src="<?= EVENT_PATH . 'add-worker-modal' . DS . 'task' . DS . 'existing' . DS . 'add.js' ?>"
        defer></script>

    <script type="module" src="<?= EVENT_PATH . 'edit-task-modal' . DS . 'open.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'edit-task-modal' . DS . 'cancel.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'edit-task-modal' . DS . 'submit.js' ?>" defer></script>
</body>

</html>