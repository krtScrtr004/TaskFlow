<?php

use App\Enumeration\WorkStatus;

if (!$task)
    throw new InvalidArgumentException('Task data is required to render the edit task modal.');

if (!$taskData)
    throw new InvalidArgumentException('Task data array is required to render the edit task modal.');

$uiState = [
    'taskHasStarted' => $taskData['startDateTime'] <= new DateTime() ? 'disabled' : '',
    'taskIsCompleted' => $taskData['status'] === WorkStatus::COMPLETED ? 'disabled' : '',
    'showWarning' => in_array($taskData['status'], [WorkStatus::COMPLETED, WorkStatus::CANCELLED])
];
?>

<section id="edit_task_modal_template" class="modal-wrapper no-display" data-taskid="<?= $taskData['id'] ?> ">

    <div class="modal-form modal flex-col black-bg">
        <!-- Heading -->
        <section class="flex-row flex-space-between">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Edit Task" title="Edit Task" height="25">
                <h3 class="title">Edit Task</h3>
            </div>

            <!-- Close Button -->
            <button id="edit_task_close_button" type="button" class="unset-button">
                <p class="red-text">✖</p>
            </button>
        </section>

        <hr>

        <?php if ($uiState['showWarning']): ?>
            <div class="cannot-edit-warning">
                <p class="white-text">Editing unavailable. Project has been completed or cancelled.</p>
            </div>
        <?php endif; ?>

        <!-- Edit Task Form -->
        <form id="edit_task_form" class="flex-col" method="POST" action="">
            <!-- Name -->
            <div class="input-label-container">
                <label for="task_name">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Task Name" title="Task Name" height="20">

                        <p>Name</p>
                    </div>
                </label>
                <input type="text" name="task_name" id="task_name" min="1" max="255" placeholder="Task Name"
                    value="<?= $taskData['name'] ?>" <?= $uiState['taskIsCompleted'] ?> required>
            </div>

            <!-- Description -->
            <div class="input-label-container">
                <label for="task_description">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Task Description"
                            title="Task Description" height="20">

                        <p>Description</p>
                    </div>
                </label>
                <textarea name="task_description" id="task_description" rows="4"
                    placeholder="Task Description (optional)" <?= $uiState['taskIsCompleted'] ?>><?= $taskData['description'] ?></textarea>
            </div>

            <!-- Secondary Info -->
            <div class="task-secondary-info flex-row">
                <!-- Start Date -->
                <div class="input-label-container">
                    <label for="task_start_datetime">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Task Start Date" title="Task Start Date"
                                height="20">

                            <p>Start Date</p>
                        </div>
                    </label>
                    <input type="date" name="task_start_datetime" id="task_start_datetime"
                        value="<?= htmlspecialchars(formatDateTime($taskData['startDateTime'], 'Y-m-d')) ?>"
                        <?= $uiState['taskHasStarted'] ?> required>
                </div>

                <!-- Completion Date -->
                <div class="input-label-container">
                    <label for="task_completion_datetime">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Task Completion Date"
                                title="Task Completion Date" height="20">

                            <p>Completion Date</p>
                        </div>
                    </label>
                    <input type="date" name="task_completion_datetime" id="task_completion_datetime"
                        value="<?= htmlspecialchars(formatDateTime($taskData['completionDateTime'], 'Y-m-d')) ?>"
                        <?= $uiState['taskIsCompleted'] ?> required>
                </div>
            </div>

            <!-- Priority -->
            <div class="input-label-container">
                <label for="task_priority">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'priority_w.svg' ?>" alt="Task Priority" title="Task Priority"
                            height="20">

                        <p>Priority</p>
                    </div>
                </label>
                <select name="task_priority" id="task_priority" <?= $uiState['taskIsCompleted'] ?>>
                    <?php $taskPriorityName = $taskData['priority']->getDisplayName() ?>
                    <option value="Low" <?= $taskPriorityName === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $taskPriorityName === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= $taskPriorityName === 'High' ? 'selected' : '' ?>>High</option>
                </select>
            </div>

            <!-- Add New Task Button -->
            <button id="edit_task_button" type="button" class="blue-bg" <?= $uiState['taskIsCompleted'] ?>>
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'edit_w.svg' ?>" alt="Edit Task" title="Edit Task" height="20">
                    <h3 class="white-text">Edit Task</h3>
                </div>
            </button>
        </form>
    </div>

</section>