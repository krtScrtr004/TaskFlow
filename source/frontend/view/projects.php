<?php

use App\Core\Me;
use App\Model\ProjectModel;
use App\Enumeration\Role;
use App\Enumeration\WorkStatus;
use App\Utility\ProjectManagerPerformanceCalculator;
use App\Utility\WorkerPerformanceCalculator;

$additionalInfo = Me::getInstance()->getAdditionalInfo();
$terminatedProjectCount = array_key_exists('terminatedProjectCount', $additionalInfo)
    ? htmlspecialchars(formatNumber($additionalInfo['terminatedProjectCount'])) : 0;

$statisticsData = [
    'performance'   => 0,
    'total'         => htmlspecialchars(formatNumber($projects?->count() ?? 0)),
    'completed'     => htmlspecialchars(formatNumber($projects?->getCountByStatus(WorkStatus::COMPLETED) ?? 0)),
    'cancelled'     => htmlspecialchars(formatNumber($projects?->getCountByStatus(WorkStatus::CANCELLED) ?? 0))
];

if (isset($projects)) {
    $calculateStatistics = Role::isProjectManager(Me::getInstance())
        ? ProjectManagerPerformanceCalculator::calculate($projects)
        : WorkerPerformanceCalculator::calculate($projects);
    $statisticsData['performance'] = htmlspecialchars(formatNumber($calculateStatistics['overallScore']));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Projects</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'projects.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="projects main-page">

        <!-- Heading -->
        <section class="heading content-section-block">
            <h3>Project History</h3>
            <p>Track all projects you've handled</p>
        </section>

        <section class="statistics content-section-block flex-row">

            <!-- Performance -->
            <div class="performance flex-col flex-child-center-v">
                <div>
                    <h3 class="start-text">Performance</h3>
                    <p>Your performance in the last 10 projects</p>
                </div>

                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'progress_w.svg' ?>" alt="Performance" title="Performance" height="45">

                    <?php
                    if ($statisticsData['performance'] >= 90) {
                        $performanceClass = 'green-text';
                    } elseif ($statisticsData['performance'] >= 75) {
                        $performanceClass = 'yellow-text';
                    } else {
                        $performanceClass = 'red-text';
                    }
                    ?>
                    <h1 class="<?= $performanceClass ?>">
                        <?= $statisticsData['performance'] . '%' ?>
                    </h1>
                </div>
            </div>

            <hr>

            <!-- Total -->
            <div class="total flex-col flex-child-center-v">
                <h3 class="start-text">Total</h3>

                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Total Projects" title="Total Projects" height="30">
                    <h3><?= $statisticsData['total'] ?></h3>
                </div>
            </div>

            <hr>

            <!-- Completed -->
            <div class="completed flex-col flex-child-center-v">
                <h3 class="start-text">Completed</h3>

                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completed Projects" title="Completed Projects"
                        height="30">
                    <h3><?= $statisticsData['completed'] ?></h3>
                </div>
            </div>

            <hr>

            <!-- Canceled -->
            <div class="cancel cancelled flex-col flex-child-center-v">
                <h3 class="start-text">Canceled</h3>

                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'close_w.svg' ?>" alt="Canceled Projects" title="Canceled Projects"
                        height="30">
                    <h3><?= $statisticsData['cancelled'] ?></h3>
                </div>
            </div>

            <hr>

            <?php if (Role::isWorker(Me::getInstance())): ?>
                <!-- Terminated -->
                <div class="cancel-terminate flex-col flex-child-center-v">
                    <h3 class="start-text">Terminated</h3>

                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'close_w.svg' ?>" alt="Terminated Projects" title="Terminated Projects"
                            height="30">
                        <h3><?= $terminatedProjectCount ?></h3>
                    </div>
                </div>
            <?php endif; ?>


        </section>

        <!-- Search Bar -->
        <section class="flex-row flex-child-end-v">
            <?= searchBar([
                'Status' => [
                    WorkStatus::PENDING->getDisplayName(),
                    WorkStatus::ON_GOING->getDisplayName(),
                    WorkStatus::COMPLETED->getDisplayName(),
                    WorkStatus::DELAYED->getDisplayName(),
                    WorkStatus::CANCELLED->getDisplayName()
                ]
            ]) ?>
        </section>

        <section class="project-grid-container">
            <!-- Projects Grid -->
            <section class="project-grid grid">
                <?php foreach ($projects as $project) {
                    echo projectGridCard($project);
                } ?>
            </section>

            <div class="sentinel"></div>

            <div
                class="no-projects-wall no-content-wall <?= $projects?->count() < 1 ? 'flex-col' : 'no-display' ?>">
                <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No projects available" title="No projects available"
                    height="70">
                <h3 class="center-text">No projects found.</h3>
            </div>
        </section>

    </main>

    <script type="module" src="<?= EVENT_PATH . 'logout.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'projects' . DS . 'infinite-scroll.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'projects' . DS . 'search.js' ?>" defer></script>
</body>

</html>