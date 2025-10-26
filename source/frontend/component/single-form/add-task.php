<?php
$projectId = $args['projectId'] ?? null;
if ($projectId === null)
    throw new ErrorException("Project ID is required to add a task.");

require_once COMPONENT_PATH . 'template' . DS . 'add-worker-modal.php';
?>

<!-- Add Task Form -->
<form id="add_task_form" class="add-task flex-row" action="" method="POST" data-projectid="<?= $projectId ?>">

    <!-- Task Details -->
    <fieldset class="task-detail flex-col">

        <!-- Heading -->
        <section class="heading">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Add New Task" title="Add New Task" height="28">
                <h3>Add New Task</h3>
            </div>
            <p>Fill in the details of the new task to be added to the project.</p>
        </section>

        <section class="task-primary-info flex-row">

            <!-- Task Name -->
            <div class="input-label-container">
                <label for="task_name">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Task Name" title="Task Name" height="20">
                        <p>Name</p>
                    </div>
                </label>

                <input type="text" class="task-name" name="task_name" id="task_name" autocomplete="on"
                    min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>" autocapitalize="on" placeholder="Enter task name"
                    required>
            </div>

            <!-- Task Schedule -->
            <div class="task-schedule flex-row">

                <!-- Task Start Date -->
                <div class="input-label-container">
                    <label for="task_start_date">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date" height="20">
                            <p>Start Date</p>
                        </div>
                    </label>

                    <input type="date" name="task_start_date" id="task_start_date"
                        value="<?= formatDateTime(new DateTime, 'Y-m-d') ?>" required>
                </div>

                <!-- Task Completion Date -->
                <div class="input-label-container">
                    <label for="task_completion_date">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completion Date" title="Completion Date"
                                height="20">
                            <p>Completion Date</p>
                        </div>
                    </label>

                    <input type="date" name="task_completion_date" id="task_completion_date"
                        value="<?= formatDateTime(new DateTime, 'Y-m-d') ?>" required>
                </div>
            </div>
        </section>

        <!-- Task Description -->
        <div class="input-label-container">
            <label for="task_description">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Task Description" title="Task Description"
                        height="20">
                    <p>Description</p>
                </div>
            </label>

            <textarea name="task_description" id="task_description" rows="4" min="<?= LONG_TEXT_MIN ?>"
                max="<?= LONG_TEXT_MAX ?>" placeholder="Enter task description"></textarea>
        </div>

        <!-- Task Priority -->
        <div class="input-label-container">
            <label for="task_priority">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'priority_w.svg' ?>" alt="Task Priority" title="Task Priority"
                        height="20">
                    <p>Priority</p>
                </div>
            </label>

            <select class="task-priority" name="task_priority" id="task_priority">
                <option value="low">Low Priority</option>
                <option value="medium" selected>Medium Priority</option>
                <option value="high">High Priority</option>
            </select>
        </div>

        <!-- Add New Task Button -->
        <div>
            <button id="add_new_task_button" type="button" class="blue-bg float-right">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Task" title="Add Task" height="20">
                    <p>Add Task</p>
                </div>
            </button>
        </div>

    </fieldset>

    <hr>

    <!-- Task Worker -->
    <fieldset class="task-worker flex-col">

        <!-- Heading -->
        <section class="heading">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Assign Workers" title="Assign Workers" height="28">
                <h3>Assign Workers</h3>
            </div>
            <p>Select workers to assign to this task.</p>
        </section>


        <!-- Task Worker List -->
        <section class="list flex-col">
            <!-- Worker Cards will be added here dynamically -->
            <div class="no-assigned-worker-wall no-content-wall flex-col">
                <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No Workers Assigned" title="No Workers Assigned"
                    height="70">
                <h3>No Workers Assigned</h3>
            </div>
        </section>

        <!-- Add Worker Button -->
        <button id="add_worker_button" type="button" class="transparent-bg">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Worker" title="Add Worker" height="20">
                <p>Add Worker</p>
            </div>
        </button>

    </fieldset>

</form>