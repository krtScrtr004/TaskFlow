<?php

use App\Core\UUID;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use App\Middleware\Csrf;
use App\Utility\ProjectProgressCalculator;

if (!$projectReport) {
    throw new Error('Project Report data is not defined.');
}

$reportData = [
    'id'                        => htmlspecialchars(UUID::toString($projectReport->getPublicId())),
    'name'                      => htmlspecialchars($projectReport->getName()),
    'startDateTime'             => htmlspecialchars(formatDateTime($projectReport->getStartDateTime())),
    'completionDateTime'        => htmlspecialchars(formatDateTime($projectReport->getCompletionDateTime())),
    'actualCompletionDateTime'  => $projectReport->getActualCompletionDateTime() ? 
        htmlspecialchars(formatDateTime($projectReport->getActualCompletionDateTime())) : 
        null,
    'status'                    => $projectReport->getStatus(),
    'workerCount'               => $projectReport->getWorkerCount(),  
    'periodicTaskCount'         => $projectReport->getPeriodicTaskCount(),
    'phases'                    => $projectReport->getPhases(),
    'topWorkers'                => $projectReport->getTopWorker(),
];

$performance = ($reportData['phases']?->count() > 0)
    ? ProjectProgressCalculator::calculate($reportData['phases'])
    : [
        'progressPercentage' => 0.0,
        'statusBreakdown' => [],
        'priorityBreakdown' => [],
        'insights' => [
            'messages' => ['No insights available.'],
            'recommendations' => ['No recommendations available.']
        ]
    ];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

    <title>Report</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'report.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="report main-page ">

        <section class="first-block flex-col">

            <!-- Overview Report -->
            <section class="overview-report content-section-block flex-col">

                <!-- Heading -->
                <div class="section-heading heading main flex-row">
                    <div class="text-w-icon"> <img src="<?= ICON_PATH . 'project_w.svg' ?>"
                            alt="<?= $reportData['name'] ?>" title="<?= $reportData['name'] ?>" height="45">

                        <div class="heading-text flex-col">
                            <h2><?= $reportData['name'] ?>'s Report</h2>
                            <p>Track the project's reports and statistics</p>
                        </div>
                    </div>

                    <?= WorkStatus::badge($reportData['status']) ?>
                </div>

                <!-- Progress Bar -->
                <div>
                    <p>This project is <strong><?= $performance['progressPercentage'] ?>%</strong> complete.</p>

                    <span class="progress-percentage no-display"
                        data-projectPercentage="<?= $performance['progressPercentage'] ?>"></span>

                    <div class="progress-container">
                        <div class="progress-bar white-text" id="project_progress_bar"></div>
                    </div>
                </div>
            </section>

            <!-- Phases Statistics -->
            <section class="phase-statistics content-section-block flex-col">
                <div class="section-heading flex-col">
                    <div class="heading-title text-w-icon">
                        <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="Phase Progress" title="Phase Progress"
                            height="20">

                        <h2>Phase Statistics</h2>
                    </div>

                    <p>Analyze the statistics of each phase within the project</p>
                </div>

                <!-- Timeline -->
                <section class="phase-timeline">
                    <div class="section-heading flex-col">
                        <div class="heading-title text-w-icon">
                            <img src="<?= ICON_PATH . 'timeline_w.svg' ?>" alt="Phase Timeline" title="Phase Timeline"
                                height="18">

                            <h3>Timeline</h3>
                        </div>

                        <p>Track the timeline of each phase within the project</p>
                    </div>
                    
                    <section class="timeline">
                        <span 
                            class="project-schedule no-display"
                            data-projectStartDateTime="<?= $reportData['startDateTime'] ?>"
                            data-projectCompletionDateTime="<?= $reportData['completionDateTime'] ?>"
                            data-projectActualCompletionDateTime="<?= $reportData['actualCompletionDateTime'] ?>"></span>

                        <div class="phase-timeline-data no-display">
                            <?php 
                            foreach ($reportData['phases'] as $phase): 
                                $name = htmlspecialchars($phase->getName());
                                $startDateTime = htmlspecialchars(formatDateTime($phase->getStartDateTime()));
                                $completionDateTime = htmlspecialchars(formatDateTime($phase->getCompletionDateTime()));
                                $actualCompletionDate = $phase->getActualCompletionDateTime() ? 
                                    htmlspecialchars(formatDateTime($phase->getActualCompletionDateTime())) : 
                                    null;
                            ?>
                                <span 
                                    data-name="<?= $name ?>"
                                    data-startDateTime="<?= $startDateTime ?>"
                                    data-completionDateTime="<?= $completionDateTime ?>"
                                    data-actualCompletionDateTime="<?= $actualCompletionDate ?>"></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="chart-container phase-timeline-container">
                            <canvas id="phase_timeline_chart" height="400" width="500"></canvas>
                        </div>
                    </section>
                </section>

                <hr>

                <!-- Phase Progress -->
                <section class="phase-progress">
                    <div class="section-heading flex-col">
                        <div class="heading-title text-w-icon">
                            <img src="<?= ICON_PATH . 'progress_w.svg' ?>" alt="Phase Progress" title="Phase Progress"
                                height="18">

                            <h3>Phase Progress</h3>
                        </div>

                        <p>Track the progress of each phase</p>
                    </div>

                    <section class="phases-list flex-col">
                        <?php 
                            foreach ($performance['phaseBreakdown'] as $phaseId => $phaseData): 
                                $reference = $reportData['phases']->get($phaseId);
                                $name = htmlspecialchars($reference->getName());
                                $startDateTime = htmlspecialchars(dateToWords($reference->getStartDateTime()));
                                $completionDateTime = htmlspecialchars(dateToWords($reference->getCompletionDateTime()));
                                $actualCompletionDateTime = $reference->getActualCompletionDateTime() ? htmlspecialchars(dateToWords($reference->getActualCompletionDateTime())) : null;
                                $progress = $phaseData['simpleProgress'] ?? 0.0;
                        ?>
                            <div class="phase-card flex-row">
                                <section class="phase-header flex-col">
                                    <h3><?= $name ?></h3>

                                    <div class="phase-schedule flex-col">
                                        <span class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Task Start Date" title="Task Start Date"
                                                height="16">

                                            <p>Start Date: 
                                                <span class="task-start-datetime">
                                                    <?= $startDateTime ?>
                                                </span>
                                            </p>
                                        </span>
                                        <span class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Task Completion Date" title="Task Completion Date"
                                                height="16">

                                            <p>Completion Date: 
                                                <span class="task-completion-datetime">
                                                    <?= $completionDateTime ?>
                                                </span>
                                            </p>
                                        </span>
                                        <?php if ($actualCompletionDateTime): ?>
                                            <span class="text-w-icon">
                                                <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Task Actual Completion Date" title="Task Actual Completion Date"
                                                    height="16">

                                                <p>Actual Completion Date: 
                                                    <span class="task-actual-completion-datetime">
                                                        <?= $actualCompletionDateTime ?>
                                                    </span>
                                                </p>
                                        <?php endif; ?>
                                    </div>
                                </section>

                                <section class="phase-progress-bar">
                                    <span class="progress-percentage no-display"
                                        data-phasePercentage="<?= $progress ?>"></span>

                                    <div class="progress-container">
                                        <div class="progress-bar white-text"></div>    
                                    </div>
                                    
                                    <p class="end-text">This phase is <strong><?= $progress ?>%</strong> complete</p>
                                </section>

                            </div>
                        <?php endforeach; ?>

                    </section>
                </section>

            </section>

            <!-- Task Statistics -->
            <section class="task-statistics content-section-block flex-col">
                <div class="section-heading flex-col">
                    <div class="heading-title text-w-icon">
                        <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Tasks Statistics" title="Tasks Statistics"
                            height="28">

                        <h2>Tasks Statistics</h2>
                    </div>

                    <p>Track the statistics of tasks within the project</p>
                </div>

                <!-- Status x Priority Distribution -->
                <section class="status-priority-distribution">
                    <div class="section-heading flex-col">
                        <div class="heading-title text-w-icon">
                            <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Tasks Statistics" title="Tasks Statistics"
                                height="18">

                            <h3>Status <span class="x">x</span> Priority Distribution</h3>
                        </div>

                        <p>Monitor the distribution of tasks by status and priority</p>
                    </div>

                    <section>
                        <?php
                        $combinationBreakdown = $performance['combinationBreakdown'] ?? [];
                        ?>
                        <!-- Combination breakdown data for chart -->
                        <div class="combination-breakdown no-display">
                            <?php foreach (WorkStatus::cases() as $status): ?>
                                <div class="status-group" data-status="<?= $status->value ?>">
                                    <?php foreach (TaskPriority::cases() as $priority): ?>
                                        <?php 
                                        $combo = $combinationBreakdown[$status->value][$priority->value] ?? [];
                                        $count = $combo['count'] ?? 0;
                                        $percentage = $combo['percentage'] ?? 0;
                                        ?>
                                        <span data-priority="<?= $priority->value ?>" 
                                            data-count="<?= $count ?>" 
                                            data-percentage="<?= $percentage ?>"></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <canvas id="status_priority_distribution" height="300" width="500"></canvas>
                    </section>

                </section>

                <hr>

                <!-- Per Phase Breakdown Table -->
                <section class="per-phase-breakdown flex-col">
                    
                    <div class="section-heading flex-col">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="Tasks Per Phase Breakdown" title="Tasks Per Phase Breakdown"
                                height="16">

                            <h3>Per Phase Breakdown</h3>
                        </div>

                        <p>Analyze the distribution of tasks across different phases of the project</p>
                    </div>

                    <?php if ($reportData['phases']?->count() > 0): ?>
                        <div class="phases-table-container black-bg">
                            <table class="phases-table black-bg">
                            
                                <thead class="black-bg">
                                    <tr>
                                        <th>Phase Name</th>
                                        <th colspan="5" class="status-header blue-bg white-text">Status</th>
                                        <th colspan="3" class="priority-header yellow-bg black-text">Priority</th>
                                    </tr>
                                    <tr class="white-bg">
                                        <th></th>
                                        <th class="cell-header black-text">Completed</th>
                                        <th class="cell-header black-text">On Going</th>
                                        <th class="cell-header black-text">Pending</th>
                                        <th class="cell-header black-text">Delayed</th>
                                        <th class="cell-header black-text">Cancelled</th>
                                        <th class="cell-header black-text">High</th>
                                        <th class="cell-header black-text">Medium</th>
                                        <th class="cell-header black-text">Low</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($reportData['phases'] as $phase): 
                                        $name = htmlspecialchars($phase->getName());
                                        $total = $performance['phaseBreakdown'][$phase->getId()]['totalTasks'] ?? 0;
                                        $phaseId = $phase->getId();
                                    ?>
                                        <tr>
                                            <td class="phase-name-cell wrap-text">
                                                <?= $name ?>
                                            </td>
                                            <td class="cell-data completed">
                                                <?= $performance['phaseBreakdown'][$phaseId]['statusBreakdown']['completed']['count'] ?? 0 ?>
                                            </td>
                                            <td class="cell-data ongoing">
                                                <?= $performance['phaseBreakdown'][$phaseId]['statusBreakdown']['onGoing']['count'] ?? 0 ?>
                                            </td>
                                            <td class="cell-data pending">
                                                <?= $performance['phaseBreakdown'][$phaseId]['statusBreakdown']['pending']['count'] ?? 0 ?>
                                            </td>
                                            <td class="cell-data delayed">
                                                <?= $performance['phaseBreakdown'][$phaseId]['statusBreakdown']['delayed']['count'] ?? 0 ?>
                                            </td>
                                            <td class="cell-data cancelled">
                                                <?= $performance['phaseBreakdown'][$phaseId]['statusBreakdown']['cancelled']['count'] ?? 0 ?>
                                            </td>
                                            <td class="cell-data high">
                                                <?= $performance['phaseBreakdown'][$phaseId]['priorityBreakdown']['high']['count'] ?? 0 ?>
                                            </td>
                                            <td class="cell-data medium">
                                                <?= $performance['phaseBreakdown'][$phaseId]['priorityBreakdown']['medium']['count'] ?? 0 ?>
                                            </td>
                                            <td class="cell-data low">
                                                <?= $performance['phaseBreakdown'][$phaseId]['priorityBreakdown']['low']['count'] ?? 0 ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No phases available for this project.</p>
                    <?php endif; ?>

                </section>

                <hr>

                <!-- Monthly Counts -->
                <section class="periodic-count flex-col">
                    <?php foreach($reportData['periodicTaskCount'] as $year => $months): ?>
                        <div class="no-display" data-year="<?= $year ?>">
                            <?php foreach($months as $month => $count): ?>
                                <span data-month="<?= $month ?>" data-count="<?= $count ?>"></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="section-heading flex-col">
                        <div class="heading text-w-icon">
                            <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Tasks Created" title="Tasks Created"
                                height="20">

                            <h3>Tasks Created</h3>
                        </div>

                        <p>Number of tasks created each period</p>
                    </div>

                    <canvas id="task_periodic_count_chart" width="500" height="200"></canvas>
                </section>

            </section>

            <!-- Top Performing Workers -->
            <section class="top-performing-workers content-section-block flex-col">
                
                <div class="section-heading flex-col">
                    <div class="heading-title text-w-icon">
                        <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Top Performing Workers" title="Top Performing Workers"
                            height="24">

                        <h2>Top Performing Workers</h2>
                    </div>

                    <p>View the workers with the highest task completion rates</p>
                </div>

                <div class="workers-table-container black-bg">
                    <table class="workers-table black-bg">
                        <thead class="black-bg">
                            <tr class="blue-bg white-text">
                                <th>Full Name</th>
                                <th>Total Tasks</th>
                                <th>Completed Tasks</th>
                                <th>Performance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $idx = 0;
                            foreach ($reportData['topWorkers'] as $worker): 
                                $fullName = htmlspecialchars($worker->getFirstName() . ' ' . $worker->getLastName());
                                $totalTasks = $worker->getAdditionalInfo('totalTasks') ?? 0;
                                $completedTasks = $worker->getAdditionalInfo('completedTasks') ?? 0;
                                $overallScore = $worker->getAdditionalInfo('overallScore') ?? 0.0;
                                $classColor = match ($idx) {
                                    0 => 'blue-text',
                                    1 => 'green-text',
                                    2 => 'yellow-text',
                                    default => 'white-text',
                                };
                                $idx++;
                            ?>
                                <tr>
                                    <td class="worker-name-cell">
                                        <strong><?= $fullName ?></strong>
                                    </td>
                                    <td class="cell-data"><?= $totalTasks ?></td>
                                    <td class="cell-data"><?= $completedTasks ?></td>
                                    <td class="cell-data <?= $classColor ?>"><?= formatNumber($overallScore) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </section>

        </section>

        <section class="second-block flex-col">

            <!-- Worker Statistics -->
            <section class="worker-statistics content-section-block flex-col center-child">
                <div class="worker-count no-display">
                    <?php foreach ($reportData['workerCount'] as $status => $stats): ?>
                        <span
                            data-status="<?= $status ?>"
                            data-count="<?= $stats['count'] ?>"
                            data-percentage="<?= $stats['percentage'] ?>"></span>
                    <?php endforeach; ?>
                </div>

                <div class="section-heading center-child flex-col">
                    <div class="heading-title text-w-icon">
                        <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Workers Statistics" title="Workers Statistics"
                            height="25">

                        <h2>Workers Statistics</h2>
                    </div>

                    <p class="center-text">See the current status of workers</p>
                </div>

                <canvas id="worker_statistics_chart" width="300" height="300"></canvas>
            </section>

            <!-- Insights and Recommendations -->
            <section class="insights-recommendations flex-col">
                <!-- Insights -->
                <section class="insights">
                    <h3 class="black-text">INSIGHTS:</h3>

                    <hr>

                    <ul class="insight-list">
                        <?php if ($performance['insights']['messages'] && count($performance['insights']['messages']) > 0): ?>
                            <?php foreach ($performance['insights']['messages'] as $insight): ?>
                                <li class="black-text"><?= $insight ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="black-text">No insights available.</li>
                        <?php endif; ?>
                    </ul>
                </section>

                <!-- Recommendations -->
                <section class="recommendations">
                    <h3>RECOMMENDATIONS:</h3>

                    <hr>

                    <ul class="recommendation-list">
                        <?php if ($performance['insights']['recommendations'] && count($performance['insights']['recommendations']) > 0): ?>
                            <?php foreach ($performance['insights']['recommendations'] as $recommendation): ?>
                                <li><?= $recommendation ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No recommendations available.</li>
                        <?php endif; ?>
                    </ul>
                </section>
            </section>
        </section>

    </main>

    <script type="module" src="<?= EVENT_PATH . 'logout.js' ?>" defer></script>
    <script src="<?= EVENT_PATH . 'report' . DS . 'luxon.min.js' ?>"></script>
    <script src="<?= PUBLIC_PATH . 'chart.umd.min.js' ?>"></script>
    <script src="<?= EVENT_PATH . 'report' . DS . 'chartjs-adapter-date-fns.js' ?>"></script>

    <script type="module" src="<?= EVENT_PATH . 'report' . DS . 'phase-timeline.js' ?>"></script>
    <script type="module" src="<?= EVENT_PATH . 'home' . DS . 'progress-bar.js' ?>"></script>
    <script type="module" src="<?= EVENT_PATH . 'report' . DS . 'phase-progress-bar.js' ?>"></script>
    <script type="module" src="<?= EVENT_PATH . 'report' . DS . 'task-periodic-count.js' ?>"></script>
    <script type="module" src="<?= EVENT_PATH . 'report' . DS . 'worker-statistics.js' ?>"></script>
    <script type="module" src="<?= EVENT_PATH . 'report' . DS . 'status_priority_distribution_count.js' ?>"></script>
</body>

</html>