<?php

use App\Container\TaskContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Enumeration\Role;
use App\Enumeration\WorkStatus;
use App\Utility\ProjectProgressCalculator;

if (!$project) {
    throw new Exception('Project data is required to render this component.');
}

$projectData = [
    'id'                        => htmlspecialchars(UUID::toString($project->getPublicId())),
    'name'                      => htmlspecialchars($project->getName()),
    'description'               => htmlspecialchars($project->getDescription()),
    'budget'                    => htmlspecialchars($project->getBudget()),
    'manager'                   => $project->getManager(),
    'startDateTime'             => htmlspecialchars(dateToWords($project->getStartDateTime())),
    'completionDateTime'        => htmlspecialchars(dateToWords($project->getCompletionDateTime())),
    'actualCompletionDateTime'  => $project->getActualCompletionDateTime()
        ? htmlspecialchars(dateToWords($project->getActualCompletionDateTime()))
        : '—',
    'status'                    => $project->getStatus(),
    'tasks'                     => $project->getTasks(),
    'phases'                    => $project->getPhases(),
    'workers'                   => $project->getWorkers()->getAssigned(),
    'progress'                  => $projectProgress ?? ProjectProgressCalculator::calculate($project->getTasks())
];

$phaseData = [
    'totalBudget'       => htmlspecialchars($projectData['phases']->getTotalBudget()),
    'maxCount'          => $projectData['phases']->count(),
    'ongoingPlace'      => $projectData['phases']->countByStatus(WorkStatus::ONGOING) < $projectData['phases']->countByStatus(WorkStatus::COMPLETED)
        ? $projectData['phases']->countByStatus(WorkStatus::ONGOING) + 1
        : $projectData['phases']->countByStatus(WorkStatus::ONGOING),
];

$phaseData['allTasks'] = new TaskContainer();
foreach ($projectData['phases'] as $phase) {
    foreach ($phase->getTasks() as $task) {
        $phaseData['allTasks']->add($task);
    }
}

$taskData = [
    'recentTasks'       => $project->getAdditionalInfo('recentTasks') ?? new TaskContainer(),
    'pendingCount'      => $phaseData['allTasks']->countByStatus(WorkStatus::PENDING),
    'ongoingCount'      => $phaseData['allTasks']->countByStatus(WorkStatus::ONGOING),
    'completedCount'    => $phaseData['allTasks']->countByStatus(WorkStatus::COMPLETED),
    'delayedCount'      => $phaseData['allTasks']->countByStatus(WorkStatus::DELAYED),
    'cancelledCount'    => $phaseData['allTasks']->countByStatus(WorkStatus::CANCELLED),
];

$workerData = [
    'totalDefaultRate'  => htmlspecialchars($project->getWorkers()->getTotalDefaultRate()),
];

$projectData['totalSpending'] = $phaseData['totalBudget'] + $workerData['totalDefaultRate'];

require_once COMPONENT_PATH . 'template/user-info-card.php';
require_once COMPONENT_PATH . 'template/add-worker-modal.php';
require_once COMPONENT_PATH . 'template/add-worker-table.php';

?>
<!-- Main Content -->
<section class="project-container main-project-content flex-col" data-projectid="<?= $projectData['id'] ?>">

    <!-- Project Primary Info -->
    <section class="project-primary-info content-section-block">
        <div class="">
            <div class="flex-row flex-space-between">

                <!-- Project Name and Status -->
                <div class="first-col text-w-icon">
                    <img src="<?= ICON_PATH . 'project_bl.svg' ?>"
                        alt="<?= $projectData['name'] ?>" title="<?= $projectData['name'] ?>" height="32">


                    <div class="name-and-status flex-row">
                        <h3 class=" project-name wrap-text">
                            <?= $projectData['name'] ?>
                        </h3>

                        <?= WorkStatus::badge($projectData['status']) ?>
                    </div>
                </div>

                <?php if (Role::isProjectManager(Me::getInstance()->getRole())): ?>
                    <div class="edit-project-container flex-row-reverse">
                        <!-- Edit Project -->
                        <a class="edit-project" href="<?= REDIRECT_PATH . 'edit-project/' . $projectData['id'] ?>">
                            <img src="<?= ICON_PATH . 'edit_dw.svg' ?>" alt="Edit Project" title="Edit Project" height="20">
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <p class="project-id"><em class="dark-white-text">
                    <?= $projectData['id'] ?>
                </em></p>
        </div>

        <p class="project-description dark-white-text start-text"><?= $projectData['description'] ?></p>
    </section>

    <!-- Secondary Info -->
    <div class="project-secondary-info flex-row">

        <!-- Main Sub-content -->
        <div class="main-sub-content flex-col">

            <!-- Project Statistics -->
            <section class="project-statistics flex-row flex-child-center-h">

                <!-- Left Side -->
                <div class="left content-section-block flex-col">

                    <!-- Upper Side -->
                    <section class="upper-statistics flex-row flex-space-between">
                        <div class="individual-statistic">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'start_dw.svg' ?>" alt="Start Date" title="Start Date" height="14">

                                <p class="dark-white-text">Start Date</p>
                            </div>
                            <p class="">
                                <?= $projectData['startDateTime'] ?>
                            </p>

                        </div>

                        <div class="individual-statistic">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'deadline_dw.svg' ?>" alt="Completion Date" title="Completion Date"
                                    height="14">

                                <p class="dark-white-text">Completion Date</p>
                            </div>
                            <p class="">
                                <?= $projectData['completionDateTime'] ?>
                            </p>
                        </div>

                        <div class="individual-statistic">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'complete_dw.svg' ?>" alt="Completed At" title="Completed At"
                                    height="15">

                                <p class="dark-white-text">Completed At</p>
                            </div>
                            <p class="">
                                <?= $projectData['actualCompletionDateTime'] ?>
                            </p>

                        </div>

                    </section>

                    <!-- Lower Side -->
                    <section>
                        <span class="total-spending no-display"
                            data-projectbudget="<?= $projectData['budget'] ?>"
                            data-totalspending="<?= $projectData['totalSpending'] ?>">
                        </span>

                        <section class="total-spending-text flex-row flex-space-between">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'budget_dw.svg' ?>" alt="Total Spending" title="Total Spending"
                                    height="14">

                                <p class="dark-white-text">Total Spending</p>
                            </div>

                            <p class="blue-text">
                                <?= '₱' . formatNumber($projectData['totalSpending'] ?? 0) ?> /
                                <?= '₱' . formatNumber($projectData['budget'] ?? 0) ?>
                            </p>
                        </section>

                        <div class="total-spending-bar-container">
                            <div id="project_total_spending_bar" class="total-spending-bar white-text"></div>
                        </div>
                    </section>

                </div>

                <!-- Right Side -->
                <div class="right content-section-block flex-col">
                    <?php $progressPercentage = htmlspecialchars(formatNumber($projectData['progress']['progressPercentage'] ?? 0.0)); ?>

                    <section class="flex-row flex-space-between">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'progress_dw.svg' ?>" alt="Project Progress" title="Project Progress"
                                height="16">

                            <p class="dark-white-text">Project Progress</p>
                        </div>

                        <p class="progress-percentage" data-projectPercentage="<?= $progressPercentage ?>">
                            <?= $progressPercentage ?> <span class="percentage-symbol light-text">%</span>
                        </p>
                    </section>

                    <section class="progress-container">
                        <div class="progress-bar white-text" id="project_progress_bar"></div>
                    </section>

                    <section class="flex-row flex-space-between">
                        <p class="dark-white-text light-text">PHASE <?= $phaseData['ongoingPlace'] ?> OUT OF <?= $phaseData['maxCount'] ?></p>
                    </section>
                </div>
            </section>

            <!-- Task Statistics -->
            <section class="task-statistics content-section-block flex-col">
                <div class="heading-title text-w-icon">
                    <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Project Manager" title="Project Manager"
                        height="20">

                    <h3>TASK ANALYTICS</h3>
                </div>

                <div class="flex-row">
                    <!-- Task Statistics Chart -->
                    <section class="task-statistics-chart flex-col">
                        <span class="task-data no-display"
                            data-pendingcount="<?= $taskData['pendingCount'] ?>"
                            data-ongoingcount="<?= $taskData['ongoingCount'] ?>"
                            data-completedcount="<?= $taskData['completedCount'] ?>"
                            data-delayedcount="<?= $taskData['delayedCount'] ?>"
                            data-cancelledcount="<?= $taskData['cancelledCount'] ?>"></span>

                        <div class="task-status-count-chart-container">
                            <canvas id="task_statistics_chart" width="400" height="250"></canvas>
                        </div>

                        <section class="task-status-count-card-container flex-row">
                            <div class="task-status-count-card black-bg flex-col">
                                <h3 class="yellow-text center-text"><?= $taskData['pendingCount'] ?></h3>
                                <p class="dark-white-text light-text center-text">PENDING</p>
                            </div>

                            <div class="task-status-count-card black-bg flex-col">
                                <h3 class="green-text center-text"><?= $taskData['ongoingCount'] ?></h3>
                                <p class="dark-white-text light-text center-text">ONGOING</p>
                            </div>

                            <div class="task-status-count-card black-bg flex-col">
                                <h3 class="blue-text center-text"><?= $taskData['completedCount'] ?></h3>
                                <p class="dark-white-text light-text center-text">COMPLETED</p>
                            </div>

                            <div class="task-status-count-card black-bg flex-col">
                                <h3 class="orange-text center-text"><?= $taskData['delayedCount'] ?></h3>
                                <p class="dark-white-text light-text center-text">DELAYED</p>
                            </div>

                            <div class="task-status-count-card black-bg flex-col">
                                <h3 class="red-text center-text"><?= $taskData['cancelledCount'] ?></h3>
                                <p class="dark-white-text light-text center-text">CANCELLED</p>
                            </div>
                        </section>
                    </section>

                    <!-- Recent Tasks -->
                    <section class="recent-tasks-container flex-col">
                        <div class="see-all-tasks end-text">
                            <a class="blue-text float-right" href="<?= REDIRECT_PATH . 'project' . DS . $projectData['id'] . DS . 'task' ?>">See All</a>
                        </div>

                        <p class="dark-white-text light-text">Recent Tasks</p>

                        <?php if ($taskData['recentTasks']->count() > 0): ?>
                            <!-- Recent Tasks Cards -->
                            <section>
                                <?php foreach ($taskData['recentTasks'] as $task): ?>
                                    <a class="recent-task-card black-bg flex-row flex-child-center-h"
                                        href="<?= REDIRECT_PATH . 'project' . DS . $projectData['id'] . DS . 'task' . DS . UUID::toString($task->getPublicId()) ?>">
                                        <section class="flex-col">
                                            <div class="flex-row">
                                                <h3 class="name single-line-ellipsis">
                                                    <?= htmlspecialchars($task->getName()) ?>
                                                </h3>
                                                <p class="priority dark-white-text light-text">
                                                    <?= $task->getPriority()->getDisplayName() ?>
                                                </p>
                                            </div>

                                            <p class="start-date-time dark-white-text light-text">
                                                <?= formatDateTime($task->getStartDateTime(), 'd-m-Y') ?>
                                            </p>
                                        </section>
                                    <?php endforeach; ?>
                            </section>
                        <?php else: ?>
                            <!-- No Recent Tasks -->
                            <div class="no-tasks-wall no-content-wall full-body-content flex-col">
                                <img src="<?= ICON_PATH . 'empty_dw.svg' ?>" alt="No recent tasks found" title="No recent tasks found"
                                    height="50">
                                <p class="center-text dark-white-text">No recent task(s) found</p>
                            </div>
                        <?php endif; ?>
                    </section>

                </div>
            </section>

            <!-- Project Phases -->
            <section class="project-phases content-section-block flex-col">
                <div class="heading-title text-w-icon">
                    <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="Project Phases" title="Project Phases" height="20">

                    <h3>PHASES</h3>
                </div>

                <!-- Phases List -->
                <div class="phase-list flex-col">
                    <?php foreach ($projectData['phases'] as $phase) {
                        // Phase List Card
                        echo phaseListCard($phase);
                    } ?>
                </div>
            </section>

            <!-- Project Actions -->
            <section class="project-actions content-section-block">
                <div class="heading-title text-w-icon">
                    <img src="<?= ICON_PATH . 'action_w.svg' ?>" alt="Project Actions" title="Project Actions"
                        height="16">

                    <h3>ACTIONS</h3>
                </div>

                <div class="action-buttons flex-row">
                    <a class="green-text inline" href="<?= REDIRECT_PATH . 'project' . DS . $projectData['id'] . DS . 'report' ?>">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'progress_g.svg' ?>" alt="View Reports And Statistics"
                                title="View Reports And Statistics" height="12">

                            <p class="green-text">View Reports And Statistics</p>
                        </div>
                    </a>

                    <?php if (Role::isProjectManager(Me::getInstance()->getRole()) && $projectData['status'] !== WorkStatus::COMPLETED && $projectData['status'] !== WorkStatus::CANCELLED): ?>
                        <button id="cancel_project_button" type="button" class="unset-button" href="">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'close_r.svg' ?>" alt="Cancel Project" title="Cancel Project" height="12">

                                <p class="red-text">Cancel Project</p>
                            </div>
                        </button>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section class="team-members">
            <section class="project-manager content-section-block flex-col">
                <div class="heading-title text-w-icon">
                    <img src="<?= ICON_PATH . 'manager_w.svg' ?>" alt="Project Manager" title="Project Manager"
                        height="16">

                    <h3>PROJECT MANAGER</h3>
                </div>

                <?= userListCard($projectData['manager']) ?>
            </section>


            <!-- Project Workers -->
            <section class="project-workers content-section-block flex-col">
                <div class="heading-title text-w-icon">
                    <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Assigned Workers" title="Assigned Workers"
                        height="20">

                    <h3>ASSIGNED WORKERS</h3>
                </div>

                <!-- Search Bar -->
                <?= searchBar() ?>

                <!-- Worker List -->
                <div class="worker-list">
                    <section class="list flex-col">
                        <?php foreach ($projectData['workers'] as $worker) {
                            // Worker List Card
                            echo userListCard($worker);
                        } ?>
                    </section>

                    <div class="sentinel"></div>

                    <!-- No Workers Wall -->
                    <div
                        class="no-workers-wall no-content-wall <?= count($projectData['workers']) > 0 ? 'no-display' : 'flex-col' ?>">
                        <img src="<?= ICON_PATH . 'empty_dw.svg' ?>" alt="No workers assigned" title="No workers assigned"
                            height="50">
                        <p class="center-text dark-white-text">No worker(s) found.</p>
                    </div>
                </div>

                <!-- Add Worker Button -->
                <?php if (
                    Role::isProjectManager(Me::getInstance()->getRole()) &&
                    $projectData['status'] !== WorkStatus::COMPLETED &&
                    $projectData['status'] !== WorkStatus::CANCELLED
                ): ?>
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

        </section>
    </div>
</section>