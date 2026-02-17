<?php

use App\Container\WorkerContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Entity\Phase;
use App\Entity\Project;
use App\Entity\Task;
use App\Enumeration\Role;
use App\Enumeration\WorkStatus;
use App\Enumeration\Priority;
use App\Enumeration\WorkerStatus;
use App\Exception\NotFoundException;
use App\Middleware\Csrf;
use App\Model\TaskWorkerModel;

if (isset($project) && !$project instanceof Project) throw new NotFoundException('Project is not defined');
if (!isset($phase) || !$phase instanceof Phase) throw new NotFoundException('Phase is not defined');
if (isset($task) && !$task instanceof Task) throw new NotFoundException('Task is not defined');

$otherData = [
    'projectId' => htmlspecialchars(UUID::toString($project->getPublicId())),
    'phaseId'   => htmlspecialchars(UUID::toString($phase->getPublicId())),
];

$taskData = [
    'id'                        => htmlspecialchars(UUID::toString($task->getPublicId())),
    'name'                      => htmlspecialchars($task->getName()),
    'description'               => htmlspecialchars($task->getDescription()),
    'workers'                   => $task->getWorkers()->getByStatus(WorkerStatus::ASSIGNED),
    'startDateTime'             => $task->getStartDateTime(),
    'completionDateTime'        => $task->getCompletionDateTime(),
    'actualCompletionDateTime'  => $task->getActualCompletionDateTime(),
    'status'                    => $task->getStatus(),
    'priority'                  => $task->getPriority(),
];

$taskModel = new TaskWorkerModel();
$buttonUiState = [
    'canComplete'   => $taskModel->worksOn(
        $task->getId(),
        Me::getInstance()->getId(),
        ['projectId' => $project->getId()]
    ),
    'canEdit'       => $task->getStatus() !== WorkStatus::COMPLETED
        && $task->getStatus() !== WorkStatus::CANCELLED,
    'canCancel'     => $taskData['status'] !== WorkStatus::COMPLETED
        && $taskData['status'] !== WorkStatus::CANCELLED,
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

    <title><?= $taskData['name'] ?? 'Task' ?></title>

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
    <?php require_once COMPONENT_PATH . 'Sidenav.php'; ?>

    <main class="view-task-info main-page flex-col"
        data-status="<?= $taskData['status']->value ?>"
        data-projectid="<?= $otherData['projectId'] ?>"
        data-phaseid="<?= $otherData['phaseId'] ?>"
        data-taskid="<?= $taskData['id'] ?>">

        <!-- Heading -->
        <section class="heading flex-row flex-space-between">

            <!-- Info -->
            <section>
                <div class="text-w-icon">
                    <button class="back-button center-child  transparent-bg">
                        <img src="<?= ICON_PATH . 'back_dw.svg' ?>" alt="Back to Selection" title="Back to Selection" height="24">
                    </button>

                    <section class="info flex-col flex-child-start-h">
                        <!-- Name & Status -->
                        <div class="center-child">
                            <h1><?= $taskData['name'] ?></h1>
                            <span class="badge">
                                <?= WorkStatus::badge($taskData['status']) ?>
                            </span>
                        </div>

                        <!-- ID -->
                        <p class="id">
                            <em class="dark-white-text">
                                <?= $taskData['id'] ?>
                            </em>
                        </p>
                    </section>
                </div>
            </section>

            <!-- Buttons -->
            <section class="buttons flex-row flex-child-end-h">
                <!-- Complete Button -->
                <?php if ($buttonUiState['canComplete']): ?>
                    <button id="complete_task_button" class="green-bg" type="button">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'complete_b.svg' ?>" alt="Complete Task" title="Complete Task" height="20">
                            <h3 class="black-text">Complete</h3>
                        </div>
                    </button>
                <?php endif; ?>

                <!-- Edit Button -->
                <?php if ($buttonUiState['canEdit']): ?>
                    <button id="edit_task_button" class="blue-bg" type="button">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'edit_w.svg' ?>" alt="Edit Task" title="Edit Task" height="20">
                            <h3>Edit</h3>
                        </div>
                    </button>
                <?php endif; ?>

                <!-- Cancel Button -->
                <?php if ($buttonUiState['canCancel']): ?>
                    <button id="cancel_task_button" class="red-bg" type="button">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'delete_w.svg' ?>" alt="Cancel Task" title="Cancel Task" height="20">
                            <h3>Cancel</h3>
                        </div>
                    </button>
                <?php endif; ?>
            </section>

        </section>

        <!-- Content -->
        <section class="content flex-row">
            <!-- Main -->
            <section class="main flex-col">

                <!-- Overview -->
                <section class="overview-section content-section-block flex-col">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'about_bl.svg' ?>" alt="Overview" title="Overview" height="20">
                        <h3 class="bold-text white-text">Overview</h3>
                    </div>

                    <p class="dark-white-text">
                        <?= $taskData['description'] ?>
                    </p>

                    <section class="details flex-row flex-space-between black-bg">
                        <!-- Start Date -->
                        <section class="flex-col">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'start_dw.svg' ?>" alt="Start Date" title="Start Date" height="16">
                                <p class="dark-white-text">Start Date</p>
                            </div>

                            <p class="bold-text"><?= dateToWords($taskData['startDateTime']) ?></p>
                        </section>

                        <!-- Completion Date -->
                        <section class="flex-col">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'deadline_dw.svg' ?>" alt="Completion Date" title="Completion Date" height="16">
                                <p class="dark-white-text">Completion Date</p>
                            </div>

                            <p class="bold-text"><?= dateToWords($taskData['completionDateTime']) ?></p>
                        </section>

                        <!-- Actual Completion Date -->
                        <?php if ($taskData['actualCompletionDateTime']): ?>
                            <section class="flex-col">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'complete_dw.svg' ?>" alt="Completed At" title="Completed At" height="16">
                                    <p class="dark-white-text">Completed At</p>
                                </div>

                                <p class="bold-text"><?= dateToWords($taskData['actualCompletionDateTime']) ?></p>
                            </section>
                        <?php endif; ?>

                        <section class="flex-col">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'priority_dw.svg' ?>" alt="Priority" title="Priority" height="16">
                                <p class="dark-white-text">Priority</p>
                            </div>

                            <p class="bold-text"><?= Priority::badge($taskData['priority']) ?></p>
                        </section>
                    </section>
                </section>

                <!-- TO-DO -->
                <section class="todo-list-section flex-col sub-section">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'task_y.svg' ?>" alt="TODO List" title="TODO List" height="20">
                        <h3 class="bold-text white-text">TO-DO List</h3>
                    </div>

                    <section class="content-section-block">
                        <!-- Add TODO -->
                        <form id="add_subtask_form" class="flex-row" method="POST" action="">
                            <input class="black-bg" type="text" name="todo_name" id="todo_name" placeholder="Add a new subtask" required>
                            <button id="add_subtask_button" class="blue-bg">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Subtask" title="Add Subtask" height="16">
                                    <h3 class="white-text">Add</h3>
                                </div>
                            </button>
                        </form>

                        <section class="todo-list">
                            <!-- TODO -->
                        </section>
                    </section>
                </section>

                <!-- Workers -->
                <section class="assigned-workers-section flex-col sub-section">
                    <section class="flex-row flex-space-between">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Assigned Workers" title="Assigned Workers" height="20">
                            <h3 class="bold-text white-text">Assigned Workers</h3>
                        </div>

                        <button id="add_worker_button" class="transparent-bg" type="button">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Worker" title="Add Worker" height="16">
                                <h3 class="white-text">Add Worker</h3>
                            </div>
                        </button>
                    </section>

                    <!-- Table -->
                    <section class="table-container content-section-block">
                        <table id="assigned_workers_table">
                            <thead>
                                <tr>
                                    <th class="dark-white-text">PROFILE</th>
                                    <th class="dark-white-text">NAME & CONTACT</th>
                                    <th class="dark-white-text">SUBTASKS</th>
                                    <th class="dark-white-text">STATUS</th>
                                    <th class="dark-white-text">ACTIONS</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($taskData['workers'] as $worker) {
                                    echo taskWorkerRow($worker);
                                } ?>
                            </tbody>
                        </table>
                    </section>

                </section>

                <!-- TODO -->
            </section>

            <!-- Side -->
            <section class="side flex-col">

                <!-- Budget -->
                <section class="budget-section content-block-section">

                </section>

            </section>
        </section>

    </main>
</body>

</html>