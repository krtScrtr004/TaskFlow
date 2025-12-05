<?php

use App\Entity\Task;
use App\Core\UUID;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;

/**
 * Renders an HTML "task grid card" for display in a task listing.
 *
 * This function builds a clickable card for a Task entity and returns the HTML as a string.
 * It performs the following operations:
 * - Reads task properties (public id, name, description, schedule, status, priority, additionalInfo).
 * - Converts UUIDs to strings via UUID::toString() for public and phase IDs.
 * - Formats start/completion datetimes using dateToWords().
 * - Escapes user-facing values with htmlspecialchars() to prevent XSS.
 * - Builds a redirect URL using REDIRECT_PATH, DS and the provided project UUID and task/phase ids.
 * - Uses TaskPriority::badge() and WorkStatus::badge() to render status/priority badges.
 * - Captures the generated HTML using output buffering (ob_start/ob_get_clean).
 *
 * @param Task $task Task instance to render. The function expects the task to provide:
 *      - getPublicId(): UUID Public identifier of the task
 *      - getName(): string Task name (will be escaped)
 *      - getDescription(): string Task description (will be escaped)
 *      - getStartDateTime(): mixed Start date/time value accepted by dateToWords()
 *      - getCompletionDateTime(): mixed Completion date/time value accepted by dateToWords()
 *      - getStatus(): mixed Status value consumed by WorkStatus::badge()
 *      - getPriority(): mixed Priority value consumed by TaskPriority::badge()
 *      - getAdditionalInfo(): array|null Additional info array where a 'phaseId' key (UUID|string) may be present
 *
 * @param UUID $projectId UUID of the project used to construct the task's redirect URL.
 *
 * @return string HTML fragment for the task card (fully escaped where applicable).
 */
function taskGridCard(Task $task, UUID $projectId): string
{
    $id                 = htmlspecialchars(UUID::toString($task->getPublicId()));
    $name               = htmlspecialchars($task->getName());
    $description        = htmlspecialchars($task->getDescription());
    $startDateTime      = htmlspecialchars(dateToWords($task->getStartDateTime()));
    $completionDateTime = htmlspecialchars(dateToWords($task->getCompletionDateTime()));
    $status             = $task->getStatus();
    $priority           = $task->getPriority();
    $phaseId            = htmlspecialchars(UUID::toString($task->getAdditionalInfo()['phaseId']) ?? '');

    $redirect = REDIRECT_PATH . 'project' . DS . UUID::toString($projectId) . DS . 'phase' . DS . $phaseId . DS . 'task' . DS . $id;

    ob_start();
    ?>
    <div class="task-grid-card">
        <a class="flex-col full-body-content" href="<?= $redirect ?>">
            <section>
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Task" title="Task" height="24">
                    <h3 class="task-name single-line-ellipsis" title="<?= $name ?>"><?= $name ?></h3>
                </div>
                <p class="task-id"><em><?= $id ?></em></p>
            </section>

            <!-- Task Description -->
            <p class="task-description multi-line-ellipsis-4" title="<?= $description ?>"><?= $description ?></p>

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
