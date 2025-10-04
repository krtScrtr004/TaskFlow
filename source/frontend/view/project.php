<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Project</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'project.css' ?>">

</head>

<body>
    <?php
    require_once COMPONENT_PATH . 'sidenav.php';

    require_once COMPONENT_PATH . 'template/worker-info-card.php';
    require_once COMPONENT_PATH . 'template/add-worker-to-project.php';
    ?>

    <main class="main-page">
        <?php
        if (!isset($project)):
            $createProject = '';

            if (Role::isProjectManager(Me::getInstance())) {
                // Only project managers can create projects
                $createProject = '<a href="' . REDIRECT_PATH . 'create-project" class="blue-text">Create Project</a>';
            }
            ?>
            <!-- No project -->
            <section class="no-project-wall no-content-wall full-body-content flex-col">
                <img src="<?= ICON_PATH . 'empty_b.svg' ?>" alt="No active project found" title="No active project found"
                    height="150">
                <h3>No active project found. <?= $createProject ?></h3>
            </section>
            <?php
        else:
            $projectData = [
                'id' => htmlspecialchars($project->getPublicId()),
                'name' => htmlspecialchars($project->getName()),
                'description' => htmlspecialchars($project->getDescription()),
                'budget' => htmlspecialchars(formatNumber(formatBudgetToPesos($project->getBudget()))),
                'startDateTime' => htmlspecialchars(formatDateTime($project->getStartDateTime())),
                'completionDateTime' => htmlspecialchars(formatDateTime($project->getCompletionDateTime())),
                'status' => $project->getStatus(),
                'tasks' => $project->getTasks(),
                'phases' => $project->getPhases(),
                'workers' => $project->getWorkers(),
            ];
            ?>
            <!-- Main Content -->
            <section class="main-project-content flex-col" data-projectid="<?= $projectData['id'] ?>">

                <!-- Project Primary Info -->
                <section class="project-primary-info content-section-block dark-white-bg">
                    <div class="">
                        <div class="flex-row flex-space-between">

                            <!-- Project Name and Status -->
                            <div class="main flex-row">
                                <div class="first-col text-w-icon">
                                    <img src="<?= ICON_PATH . 'project_b.svg' ?>" alt="<?= $projectData['name'] ?>"
                                        title="<?= $projectData['name'] ?>" height="24">

                                    <h3 class="project-name wrap-text"><?= $projectData['name'] ?></h3>
                                </div>

                                <?= WorkStatus::badge($projectData['status']) ?>
                            </div>

                            <?php if (Role::isProjectManager(Me::getInstance())): ?>
                                <div>
                                    <!-- Edit Project -->
                                    <a class="edit-project" href="<?= REDIRECT_PATH . 'edit-project/' . $projectData['id'] ?>">
                                        <img src="<?= ICON_PATH . 'edit_b.svg' ?>" alt="Edit Project" title="Edit Project"
                                            height="24">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <p class="project-id"><em><?= $projectData['id'] ?></em></p>
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
                        <section
                            class="project-statistics content-section-block flex-row flex-child-center-h dark-white-bg">

                            <!-- Left Side -->
                            <div class="left grid">

                                <div class="first-col text-w-icon">
                                    <img src="<?= ICON_PATH . 'start_b.svg' ?>" alt="Start Date" title="Start Date"
                                        height="20">

                                    <h3>Start Date</h3>
                                </div>
                                <p class="second-col"><?= $projectData['startDateTime'] ?></p>

                                <div class="first-col text-w-icon">
                                    <img src="<?= ICON_PATH . 'deadline_b.svg' ?>" alt="Completion Date"
                                        title="Completion Date" height="20">

                                    <h3>Completion Date</h3>
                                </div>
                                <p class="second-col"><?= $projectData['completionDateTime'] ?></p>

                                <?php
                                if ($projectData['status'] === WorkStatus::COMPLETED):
                                    $actualCompletionDate = htmlspecialchars(formatDateTime($project->getActualCompletionDateTime()));
                                    ?>
                                    <div class="first-col text-w-icon">
                                        <img src="<?= ICON_PATH . 'complete_b.svg' ?>" alt="Completed At" title="Completed At"
                                            height="20">

                                        <h3>Completed At</h3>
                                    </div>
                                    <p class="second-col"><?= $actualCompletionDate ?></p>
                                <?php endif; ?>

                                <div class="first-col text-w-icon">
                                    <img src="<?= ICON_PATH . 'budget_b.svg' ?>" alt="Budget" title="Budget" height="20">

                                    <h3>Budget</h3>
                                </div>
                                <p class="second-col"><?= PESO_SIGN . ' ' . $projectData['budget'] ?></p>
                            </div>

                            <!-- Right Side -->
                            <div class="right">
                                <?php
                                $projectProgress = ProjectProgressCalculator::calculateProjectProgress($projectData['tasks']);
                                $progressPercentage = htmlspecialchars(formatNumber($projectProgress['progressPercentage']));
                                ?>

                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'progress_b.svg' ?>" alt="Project Progress"
                                        title="Project Progress" height="20">

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
                        <section class="task-statistics content-section-block flex-col dark-white-bg">
                            <div class="heading flex-row flex-space-between">
                                <div class="heading-title text-w-icon">
                                    <img src="<?= ICON_PATH . 'task_b.svg' ?>" alt="Task Statistics" title="Task Statistics"
                                        height="20">

                                    <h3>Task Statistics</h3>
                                </div>
                                <!-- TODO: Add redirect link -->
                                <a href="#" class="blue-text">See All</a>
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
                                        data-ongoing="<?= $statusPercentages['ongoing'] ?>"
                                        data-completed="<?= $statusPercentages['completed'] ?>"
                                        data-delayed="<?= $statusPercentages['delayed'] ?>"
                                        data-cancelled="<?= $statusPercentages['cancelled'] ?>"
                                        class="status-percentage no-display">
                                    </div>

                                    <div class="first-col text-w-icon">
                                        <img src="<?= ICON_PATH . 'status_b.svg' ?>" alt="Task Status Distribution"
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
                                        data-medium="<?= $priorityPercentages['medium'] ?>"
                                        data-high="<?= $priorityPercentages['high'] ?>"
                                        class="priority-percentage no-display">
                                    </div>

                                    <div class="first-col text-w-icon">
                                        <img src="<?= ICON_PATH . 'priority_b.svg' ?>" alt="Task Priority Distribution"
                                            title="Task Priority Distribution" height="20">

                                        <h3>Task Priority Distribution</h3>
                                    </div>
                                    <canvas id="task_priority_chart" width="400" height="200"></canvas>
                                </div>

                            </section>
                        </section>

                        <!-- Project Phases -->
                        <section class="project-phases content-section-block flex-col dark-white-bg">
                            <div class="heading-title text-w-icon">
                                <img src="<?= ICON_PATH . 'phase_b.svg' ?>" alt="Project Phases" title="Project Phases"
                                    height="20">

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
                            <section class="project-actions content-section-block white-bg">
                                <div class="heading-title text-w-icon">
                                    <img src="<?= ICON_PATH . 'action_b.svg' ?>" alt="Project Actions" title="Project Actions"
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
                    <section class="project-workers content-section-block flex-col dark-white-bg">
                        <div class="heading-title text-w-icon">
                            <img src="<?= ICON_PATH . 'worker_b.svg' ?>" alt="Assigned Workers" title="Assigned Workers"
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
                                <img src="<?= ICON_PATH . 'empty_b.svg' ?>" alt="No workers assigned"
                                    title="No workers assigned" height="70">
                                <h3 class="center-text">No workers assigned to this project.</h3>
                            </div>
                        </div>

                        <!-- Add Worker Button -->
                        <?php if (Role::isProjectManager(Me::getInstance())): ?>
                            <div class="">
                                <button id="add_worker_button" type="button" class="float-right blue-bg"
                                    data-projectId="<?= $projectId ?>">
                                    <div class="heading-title text-w-icon center-child">
                                        <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Worker" title="Add Worker"
                                            height="18">

                                        <h3 class="white-text">Add Worker</h3>
                                    </div>
                                </button>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php if (isset($project)): ?>
        <script src="<?= PUBLIC_PATH . 'chart.umd.min.js' ?>"></script>

        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'progress-bar.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'task-chart.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'create-worker-card.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'terminate-worker.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'cancel.js' ?>"></script>

        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'add-worker-modal' . DS . 'open.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'add-worker-modal' . DS . 'close.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'add-worker-modal' . DS . 'search.js' ?>"></script>
        <script type="module" src="<?= EVENT_PATH . 'project' . DS . 'add-worker-modal' . DS . 'add.js' ?>"></script>
    <?php endif; ?>
</body>

</html>