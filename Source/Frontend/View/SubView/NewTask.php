<?php

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
        <section class="content">

        </section>

    </main>
</body>

</html>