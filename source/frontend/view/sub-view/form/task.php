<?php

use App\Enumeration\TaskPriority;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create A Task</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="icon" type="image/x-icon" href="<?= IMAGE_PATH . 'logo-dark.ico' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'form' . DS . 'task.css' ?>">
</head>

<body>
    <main class="task-creation-form main-page center-child">
        <form id="create_task_form" class="content-section-block flex-row" action="" method="POST">
            <!-- Task Info -->
            <fieldset id="task_info">
                <!-- Back Button -->
                <div class="back-container flex-row">
                    <button class="back-button unset-button">
                        <img src="<?= ICON_PATH . 'back_w.svg' ?>" alt="Back" title="Back" height="18">
                    </button>

                    <p class="">Back To Dashboard</p>
                </div>

                <!-- Form Section -->
                <section class="form-section flex-col">
                    <!-- Heading -->
                    <section class="heading">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Task" title="Task" height="24">
                            <h2 class="">Create A New Task</h2>
                        </div>
                        <p class="dark-white-text light-text">
                            Fill in the details below to create a new task
                        </p>
                    </section>

                    <!-- Input Fields -->
                    <section class="input-fields flex-col">
                        <div class="multiple-input-row flex-row">
                            <!-- Name -->
                            <div class="input-rules-container">
                                <div class="input-label-container">
                                    <label for="name">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Name" title="Name" height="18">
                                            <p class="">Name</p>
                                        </div>
                                    </label>
                                    <input type="text" id="name" name="name" placeholder="Name" required>
                                </div>

                                <?= workNameRules() ?>
                            </div>

                            <!-- Start Date Time -->
                            <div class="input-rules-container">
                                <div class="input-label-container">
                                    <label for="start_date_time">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date" height="18">
                                            <p class="">Start Date</p>
                                        </div>
                                    </label>
                                    <input type="date" id="start_date_time" name="start_date_time" required>
                                </div>

                                <?= workStartDateTimeRules(2) ?>
                            </div>

                            <!-- Completion Date Time -->
                            <div class="input-rules-container">
                                <div class="input-label-container">
                                    <label for="completion_date_time">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completion Date" title="Completion Date" height="18">
                                            <p class="">Completion Date</p>
                                        </div>
                                    </label>
                                    <input type="date" id="completion_date_time" name="completion_date_time" required>
                                </div>

                                <?= workCompletionDateTimeRules(2) ?>
                            </div>

                        </div>

                        <!-- Description -->
                        <div class="input-rules-container">
                            <div class="input-label-container">
                                <label for="description">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Description" title="Description" height="18">
                                        <p class="">Description</p>
                                    </div>
                                </label>

                                <textarea name="description" id="description" placeholder="Description" rows="8"></textarea>
                            </div>

                            <?= workDescriptionRules() ?>
                        </div>

                        <div class="multiple-input-row flex-row">
                            <!-- Priority -->
                            <div class="input-label-container">
                                <label for="priority">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'priority_w.svg' ?>" alt="Priority" title="Priority" height="18">
                                        <p class="">Priority</p>
                                    </div>
                                </label>

                                <select name="priority" id="priority" required>
                                    <?php foreach (TaskPriority::cases() as $priority) : ?>
                                        <option value="<?= $priority->value ?>"><?= $priority->getDisplayName() ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Estimated Cost -->
                            <div class="input-rules-container">
                                <div class="input-label-container">
                                    <label for="estimated_cost">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'budget_w.svg' ?>" alt="Budget" title="Budget" height="18">
                                            <p class="">Estimated Cost</p>
                                        </div>
                                    </label>

                                    <input type="number" step="0.01" min="0" name="estimated_cost" id="estimated_cost" placeholder="Estimated Cost">
                                </div>

                                <?= workBudgetRules(2) ?>
                            </div>
                        </div>

                        <!-- Budget Note -->
                        <div class="input-rules-container">
                            <div class="input-label-container">
                                <label for="budget_note">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Budget Note" title="Budget Note" height="18">
                                        <p class="">Budget Note</p>
                                    </div>
                                </label>

                                <textarea name="budget_note" id="budget_note" placeholder="Budget Note" rows="3"></textarea>
                            </div>

                            <?= workDescriptionRules() ?>
                        </div>

                    </section>

                </section>

                <!-- Create Button -->
                <button class="blue-bg">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Create Task" title="Create Task" height="20">
                        <h3>Create Task</h3>
                    </div>
                </button>
            </fieldset>

            <!-- WWorker Info -->
            <fieldset id="worker_info" class="light-black-bg">
                <!-- Heading -->
                <section class="heading">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Workers" title="Workers" height="24">
                        <h2 class="">Assign Workers</h2>
                    </div>
                    <p class="dark-white-text light-text">
                        Select workers to assign to this task
                    </p>
                </section>

                <section class="selected-worker-list flex-col no-display">
                    <!-- Selected Workers Will Appear Here -->
                </section>

                <div class="no-workers-wall no-content-wall flex-col">
                    <img src="<?= ICON_PATH . 'empty_dw.svg' ?>" alt="No Workers Assigned" title="No Workers Assigned" height="64">
                    <p class="dark-white">No Workers Assigned</p>
                </div>

                <!-- Add Worker Button -->
                <button id="add_worker_button" type="button">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Worker" title="Add Worker" height="24">
                        <h3 class="">Add Worker</h3>
                    </div>
                </button>

            </fieldset>

        </form>
    </main>
</body>

</html>