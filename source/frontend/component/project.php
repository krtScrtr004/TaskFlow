<?php
if (!$project) {
    throw new Exception('Project data is required to render this component.');
}

require_once COMPONENT_PATH . 'template/worker-info-card.php';
require_once COMPONENT_PATH . 'template/add-worker-modal.php';

$projectData = [
    'id' => htmlspecialchars($project->getPublicId()),
    'name' => htmlspecialchars($project->getName()),
    'description' => htmlspecialchars($project->getDescription()),
    'budget' => htmlspecialchars(formatNumber($project->getBudget())),
    'startDateTime' => htmlspecialchars(dateToWords($project->getStartDateTime())),
    'completionDateTime' => htmlspecialchars(dateToWords($project->getCompletionDateTime())),
    'status' => $project->getStatus(),
    'tasks' => $project->getTasks(),
    'phases' => $project->getPhases(),
    'workers' => $project->getWorkers(),
];
?>
<!-- Main Content -->
<section class="project-container main-project-content flex-col" data-projectid="<?= $projectData['id'] ?>">

    <!-- Project Primary Info -->
    <section class="project-primary-info content-section-block">
        <div class="">
            <div class="flex-row flex-space-between">

                <!-- Project Name and Status -->
                <div class="main flex-row">
                    <div class=" first-col text-w-icon"> <img src="<?= ICON_PATH . 'project_w.svg' ?>"
                            alt="<?= $projectData['name'] ?>" title="<?= $projectData['name'] ?>" height="24">

                        <h3 class=" project-name wrap-text">
                            <?= $projectData['name'] ?>
                        </h3>
                    </div>

                    <?= WorkStatus::badge($projectData['status']) ?>
                </div>

                <?php if (Role::isProjectManager(Me::getInstance())): ?>
                    <div>
                        <!-- Edit Project -->
                        <a class="edit-project" href="<?= REDIRECT_PATH . 'edit-project/' . $projectData['id'] ?>">
                            <img src="<?= ICON_PATH . 'edit_w.svg' ?>" alt="Edit Project" title="Edit Project" height="24">
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <p class="project-id"><em>
                    <?= $projectData['id'] ?>
                </em></p>
        </div>

        <p class="project-description start-text">
            <?= $projectData['description'] ?>
        </p>
    </section>

    <!-- Secondary Info -->
    <div class="project-secondary-info flex-row">

        <!-- Main Sub-content -->
        <div class="main-sub-content flex-col">

            <!-- Project Statistics -->
            <section class="project-statistics content-section-block flex-row flex-child-center-h">

                <!-- Left Side -->
                <div class="left grid">

                    <div class="first-col text-w-icon">
                        <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date" height="20">

                        <h3>Start Date</h3>
                    </div>
                    <p class="second-col">
                        <?= $projectData['startDateTime'] ?>
                    </p>

                    <div class="first-col text-w-icon">
                        <img src="<?= ICON_PATH . 'deadline_w.svg' ?>" alt="Completion Date" title="Completion Date"
                            height="20">

                        <h3>Completion Date</h3>
                    </div>
                    <p class="second-col"><?= $projectData['completionDateTime'] ?></p>

                    <?php
                    if ($projectData['status'] === WorkStatus::COMPLETED):
                        $actualCompletionDate = htmlspecialchars(formatDateTime($project->getActualCompletionDateTime()));
                        ?>
                        <div class="first-col text-w-icon">
                            <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completed At" title="Completed At"
                                height="20">

                            <h3>Completed At</h3>
                        </div>
                        <p class="second-col">
                            <?= $actualCompletionDate ?>
                        </p>
                    <?php endif; ?>

                    <div class="first-col text-w-icon">
                        <img src="<?= ICON_PATH . 'budget_w.svg' ?>" alt="Budget" title="Budget" height="20">

                        <h3>Budget</h3>
                    </div>
                    <p class="second-col">
                        <?= PESO_SIGN . ' ' . $projectData['budget'] ?>
                    </p>
                </div>

                <!-- Right Side -->
                <div class="right">
                    <?php
                    $projectProgress = ProjectProgressCalculator::calculateProjectProgress($projectData['tasks']);
                    $progressPercentage = htmlspecialchars(formatNumber($projectProgress['progressPercentage']));
                    ?>

                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'progress_w.svg' ?>" alt="Project Progress" title="Project Progress"
                            height="20">

                        <h3>Project Progress</h3>
                    </div>

                    <p class="progress-percentage" data-projectPercentage="<?= $progressPercentage ?>">
                        <?= $progressPercentage ?>%
                    </p>

                    <div class="progress-container">
                        <div class="progress-bar white-text" id="project_progress_bar"></div>
                    </div>
                </div>
            </section>

            <!-- Task Statistics -->
            <section class="task-statistics content-section-block flex-col">
                <div class="heading flex-row flex-space-between">
                    <div class=" heading-title text-w-icon">
                        <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Task Statistics" title="Task Statistics"
                            height="20">

                        <h3>Task Statistics</h3>
                    </div>
                    <!-- TODO: Add redirect link -->
                    <a href="<?= REDIRECT_PATH . 'project' . DS . $projectData['id'] . DS . 'task' ?>" class="blue-text">See All</a>
                </div>

                <!-- Task Statistics Chart -->
                <section class="task-statistics-chart center-child">

                    <!-- Task Status Chart -->
                    <div class="task-status-chart chart-container">
                        <?php
                        $statusBreakdown = $projectProgress['statusBreakdown'];
                        $statusPercentages = [
                            'pending' => $statusBreakdown[WorkStatus::PENDING->value]['percentage'] ?? 0,
                            'ongoing' => $statusBreakdown[WorkStatus::ON_GOING->value]['percentage'] ?? 0,
                            'completed' => $statusBreakdown[WorkStatus::COMPLETED->value]['percentage'] ?? 0,
                            'delayed' => $statusBreakdown[WorkStatus::DELAYED->value]['percentage'] ?? 0,
                            'cancelled' => $statusBreakdown[WorkStatus::CANCELLED->value]['percentage'] ?? 0,
                        ];

                        ?>
                        <div data-pending="<?= $statusPercentages['pending'] ?>"
                            data-ongoing=" <?= $statusPercentages['ongoing'] ?>"
                            data-completed="<?= $statusPercentages['completed'] ?>"
                            data-delayed="<?= $statusPercentages['delayed'] ?>"
                            data-cancelled="<?= $statusPercentages['cancelled'] ?>"
                            class="status-percentage no-display">
                        </div>

                        <div class="first-col text-w-icon">
                            <img src="<?= ICON_PATH . 'status_w.svg' ?>" alt="Task Status Distribution"
                                title="Task Status Distribution" height="20">

                            <h3>Task Status Distribution</h3>
                        </div>
                        <canvas id="task_status_chart" width="400" height="200"></canvas>
                    </div>

                    <!-- Task Priority Chart -->
                    <div class="task-priority-chart chart-container">
                        <?php
                        $priorityBreakdown = $projectProgress['priorityBreakdown'];
                        $priorityPercentages = [
                            'low' => $priorityBreakdown[TaskPriority::LOW->value]['percentage'] ?? 0,
                            'medium' => $priorityBreakdown[TaskPriority::MEDIUM->value]['percentage'] ?? 0,
                            'high' => $priorityBreakdown[TaskPriority::HIGH->value]['percentage'] ?? 0,
                        ];
                        ?>
                        <div data-low="<?= $priorityPercentages['low'] ?>"
                            data-medium=" <?= $priorityPercentages['medium'] ?>" data-high="
        <?= $priorityPercentages['high'] ?>" class="priority-percentage no-display">
                        </div>

                        <div class="first-col text-w-icon">
                            <img src="<?= ICON_PATH . 'priority_w.svg' ?>" alt="Task Priority Distribution"
                                title="Task Priority Distribution" height="20">

                            <h3>Task Priority Distribution</h3>
                        </div>
                        <canvas id="task_priority_chart" width="400" height="200"></canvas>
                    </div>

                </section>
            </section>

            <!-- Project Phases -->
            <section class="project-phases content-section-block flex-col">
                <div class="heading-title text-w-icon">
                    <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="Project Phases" title="Project Phases" height="20">

                    <h3>Project Phases</h3>
                </div>

                <!-- Phases List -->
                <div class="phase-list flex-col">
                    <?php foreach ($projectData['phases'] as $phase) {
                        // Phase List Card
                        echo phaseListCard($phase);
                    } ?>

                    <hr>
                </div>
            </section>

            <?php if (Role::isProjectManager(Me::getInstance())): ?>
                <!-- Project Actions -->
                <section class="project-actions content-section-block">
                    <div class="heading-title text-w-icon">
                        <img src="<?= ICON_PATH . 'action_w.svg' ?>" alt="Project Actions" title="Project Actions"
                            height="20">

                        <h3>Actions</h3>
                    </div>

                    <hr>

                    <button id="cancel_project_button" type="button" class="unset-button" href="">
                        Cancel This Project
                    </button>
                </section>
            <?php endif; ?>
        </div>

        <!-- Project Workers -->
        <section class="project-workers content-section-block flex-col">
            <div class="heading-title text-w-icon">
                <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Assigned Workers" title="Assigned Workers"
                    height="20">

                <h3>Assigned Workers</h3>
            </div>

            <!-- Worker List -->
            <div class="worker-list">
                <?php foreach ($projectData['workers'] as $worker) {
                    // Worker List Card
                    echo workerListCard($worker);
                } ?>

                <!-- No Workers Wall -->
                <div
                    class="no-workers-wall no-content-wall <?= $projectData['workers']->count() > 0 ? 'no-display' : 'flex-col' ?>">
                    <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No workers assigned" title="No workers assigned"
                        height="70">
                    <h3 class="center-text">No workers assigned to this project.</h3>
                </div>
            </div>

            <!-- Add Worker Button -->
            <?php if (Role::isProjectManager(Me::getInstance())): ?>
                <div class="">
                    <button id="add_worker_button" type="button" class="float-right blue-bg">
                        <div class="heading-title text-w-icon center-child">
                            <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Worker" title="Add Worker" height="18">

                            <h3 class="white-text">Add Worker</h3>
                        </div>
                    </button>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>