<?php
if (!isset($projects))
    throw new ErrorException('Projects data are required to render this view');

$additionalInfo = Me::getInstance()->getAdditionalInfo();
$terminationCount = array_key_exists('terminationCount', $additionalInfo)
    ? htmlspecialchars(formatNumber($additionalInfo['terminationCount'])) : 0;

$statisticsData = [
    'total' => htmlspecialchars(formatNumber($projects->count())),
    'completed' => htmlspecialchars(formatNumber($projects->getCountByStatus(WorkStatus::COMPLETED))),
    'cancelled' => htmlspecialchars(formatNumber($projects->getCountByStatus(WorkStatus::CANCELLED)))
];

$calculateStatistics = [];
if (Role::isProjectManager(Me::getInstance())) {
    $calculateStatistics = ProjectManagerPerformanceCalculator::calculate($projects);
} else {
    // TODO: Get all user tasks (LATEST 10 projects only)
    $workerTasks = TaskModel::all();
    $calculateStatistics = WorkerPerformanceCalculator::calculate($workerTasks);
}
$statisticsData['performance'] = htmlspecialchars(formatNumber($calculateStatistics['overallScore']));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>History</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'history.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="history main-page">

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
                    switch ($statisticsData['performance']) {
                        case $statisticsData['performance'] >= 90:
                            $performanceClass = 'green-text';
                            break;
                        case $statisticsData['performance'] >= 75:
                            $performanceClass = 'yellow-text';
                            break;
                        default:
                            $performanceClass = 'red-text';
                            break;
                    }
                    ?>
                    <h1 class="<?= $performanceClass ?>">
                        <?= $statisticsData['performance'] . '%' ?>
                    </h1>
                </div>
            </div>

            <hr>

            <!-- Total -->
            <div class="flex-col flex-child-center-v">
                <h3 class="start-text">Total</h3>

                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Total Projects" title="Total Projects" height="30">
                    <h3><?= $statisticsData['total'] ?></h3>
                </div>
            </div>

            <hr>

            <!-- Completed -->
            <div class="flex-col flex-child-center-v">
                <h3 class="start-text">Completed</h3>

                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completed Projects" title="Completed Projects"
                        height="30">
                    <h3><?= $statisticsData['completed'] ?></h3>
                </div>
            </div>

            <hr>

            <!-- Canceled -->
            <div class="cancel flex-col flex-child-center-v">
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
                        <h3><?= $terminationCount ?></h3>
                    </div>
                </div>
            <?php endif; ?>


        </section>

        <!-- Search Bar -->
        <section class="flex-row flex-child-end-v">
            <?= searchBar([
                'Status' => [
                    'pending',
                    'onGoing',
                    'completed',
                    'delayed',
                    'cancelled'
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
        </section>

    </main>

    <script type="module" src="<?= EVENT_PATH . 'history' . DS . 'infinite-scroll.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'history' . DS . 'search.js' ?>" defer></script>
</body>

</html>