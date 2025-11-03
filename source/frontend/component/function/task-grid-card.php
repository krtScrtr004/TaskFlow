<?php

use App\Entity\Task;
use App\Core\UUID;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;

function taskGridCard(Task $task, $projectId): string
{
    $id = htmlspecialchars(UUID::toString($task->getPublicId()));
    $name = htmlspecialchars($task->getName());
    $description = htmlspecialchars($task->getDescription());
    $startDateTime = htmlspecialchars(dateToWords($task->getStartDateTime()));
    $completionDateTime = htmlspecialchars(dateToWords($task->getCompletionDateTime()));
    $status = $task->getStatus();
    $priority = $task->getPriority();

    $redirect = REDIRECT_PATH . 'project' . DS . UUID::toString($projectId) . DS . 'task' . DS . $id;

    ob_start();
    ?>
    <div class="task-grid-card">
        <a class="flex-col full-body-content" href="<?= $redirect ?>">
            <section>
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Task" title="Task" height="24">
                    <h3 class="task-name single-line-ellipsis"><?= $name ?></h3>
                </div>
                <p class="task-id"><em><?= $id ?></em></p>
            </section>

            <!-- Task Description -->
            <p class="task-description multi-line-ellipsis-5" title="<?= $description ?>">
                <?= $description ?>
            </p>

            <!-- Task Schedule -->
            <section class="task-schedule flex-col">
                <!-- Start Date -->
                <div class="flex-row">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date" height="20">
                        <p>Start: </p>
                    </div>

                    <p><strong><?= $startDateTime ?></strong></p>
                </div>

                <!-- Completion Date -->
                <div class="flex-row">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completion Date" title="Completion Date"
                            height="20">
                        <p>End: </p>
                    </div>

                    <p><strong><?= $completionDateTime ?></strong></p>
            </section>

            <section class="task-badge flex-row flex-child-end-h">
                <?php
                echo TaskPriority::badge($priority);
                echo WorkStatus::badge($status)
                    ?>
            </section>

        </a>
    </div>
    <?php
    return ob_get_clean();
}
