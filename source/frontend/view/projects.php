<?php

use App\Core\Me;
use App\Model\ProjectModel;
use App\Enumeration\Role;
use App\Enumeration\WorkStatus;
use App\Middleware\Csrf;
use App\Model\UserModel;
use App\Utility\ProjectManagerPerformanceCalculator;
use App\Utility\WorkerPerformanceCalculator;

$additionalInfo = Me::getInstance()->getAdditionalInfo();
$terminatedProjectCount = array_key_exists('terminatedProjectCount', $additionalInfo)
    ? htmlspecialchars(formatNumber($additionalInfo['terminatedProjectCount'])) : 0;

$statisticsData = [
    'performance'   => 0,
    'total'         => htmlspecialchars(formatNumber($projects?->count() ?? 0)),
    'completed'     => htmlspecialchars(formatNumber($projects?->getCountByStatus(WorkStatus::COMPLETED) ?? 0)),
    'cancelled'     => htmlspecialchars(formatNumber($projects?->getCountByStatus(WorkStatus::CANCELLED) ?? 0)),
    'messages'      => [
        'insights'          => [],
        'recommendations'   => []
    ]
];

if (isset($projects)) {
    $userInfo = UserModel::findById(Me::getInstance()->getId());
    $calculateStatistics = Role::isProjectManager(Me::getInstance())
        ? ProjectManagerPerformanceCalculator::calculate($userInfo->getAdditionalInfo('projectHistory'))
        : WorkerPerformanceCalculator::calculate($userInfo->getAdditionalInfo('projectHistory'));
    $statisticsData['performance'] = htmlspecialchars(formatNumber($calculateStatistics['overallScore']));
    $statisticsData['messages'] = $calculateStatistics['messages'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

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

        <!-- Statistics -->
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

        <!-- Insights and Recommendations -->
        <section class="insights-recommendations content-section-block flex-row">
            <!-- Insights -->
            <section class="insights flex-col">
                <h3>Insights</h3>
                <div class="insights-list flex-col">
                    <?php
                    echo '<ul>';
                    if (count($statisticsData['messages']['insights']) > 0) {
                        foreach ($statisticsData['messages']['insights'] as $insight) {
                            echo '<li>' . $insight . '</li>';
                        }
                    } else {
                        echo '<li>No insights available at the moment.</li>';
                    }
                    echo '</ul>';
                    ?>
                </div>
            </section>

            <!-- Recommendations -->
            <section class="recommendations flex-col">
                <h3>Recommendations</h3>
                <div class="recommendations-list flex-col">
                    <?php
                    echo '<ul>';
                    if (count($statisticsData['messages']['recommendations']) > 0) {
                        foreach ($statisticsData['messages']['recommendations'] as $recommendation) {
                            echo '<li>' . htmlspecialchars($recommendation) . '</li>';
                        }
                    } else {
                        echo '<li>No recommendations available at the moment.</li>';
                    }
                    echo '</ul>';
                    ?>
                </div>
            </section>

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