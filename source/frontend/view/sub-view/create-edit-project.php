<?php
// TODO: Fix table width on smaller screens

use App\Container\WorkerContainer;
use App\Entity\User;
use App\Middleware\Csrf;

$uiState = [];
$projectData = [
    'name'                      => '',
    'description'               => '',
    'budget'                    => '',
    'maxWorkers'                => '',
    'workers'                   => [],
    'phases'                    => [],
    'startDateTime'             => '',
    'completionDateTime'        => '',
    'actualCompletionDateTime'  => ''
];

if ($project) {
    $uiState['pageName'] = 'Edit Project';

    $projectData['name'] = htmlspecialchars($project->getName());
    $projectData['description'] = htmlspecialchars($project->getDescription());
    $projectData['budget'] = $project->getBudget();
    $projectData['maxWorkers'] = $project->getMaxWorkers();
    $projectData['workers'] = $project->getWorkers()?->getItems();
    $projectData['phases'] = $project->getPhases();
    $projectData['startDateTime'] = formatDateTime($project->getStartDateTime(), 'Y-m-d');
    $projectData['completionDateTime'] = formatDateTime($project->getCompletionDateTime(), 'Y-m-d');
    $projectData['actualCompletionDateTime'] = $project->getActualCompletionDateTime()
        ? formatDateTime($project->getActualCompletionDateTime(), 'Y-m-d')
        : null;

    $uiState['phaseNoWall'] = count($projectData['phases'] ?? []) > 0 ? 'no-display' : 'flex-col';
    $uiState['workerNoWall'] = count($projectData['workers'] ?? []) > 0 ? 'no-display' : 'flex-col';
} else {
    $uiState['pageName'] = 'Create Project';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

    <title><?= $uiState['pageName'] ?></title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="icon" type="image/x-icon" href="<?= IMAGE_PATH . 'logo-dark.ico' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'project-form.css' ?>">

</head>

<body>
    <?php
    require_once COMPONENT_PATH . 'sidenav.php';

    include_once COMPONENT_PATH . 'function' . DS . 'selected-worker-row.php';
    include_once COMPONENT_PATH . 'function' . DS . 'search-bar.php';
    ?>

    <main class="project-form flex-col flex-child-center-h main-page">
        <section class="flex-col relative">
            <!-- Header -->
            <header class="flex-row flex-space-between flex-child-center-h sticky black-bg">
                <div class="flex-row">
                    <a class="text-w-icon" href="#info_section">
                        <img src="<?= ICON_PATH . 'project_w.svg' ?>" alt="Info" title="Info" height="30">
                        <h3>Info</h3>
                    </a>

                    <a class="text-w-icon" href="#phase_section">
                        <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="Phase" title="Phase" height="30">
                        <h3>Phases</h3>
                    </a>

                    <a class="text-w-icon" href="#workers_section">
                        <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Workers" title="Workers" height="30">
                        <h3>Workers</h3>
                    </a>
                </div>

                <button id="create_project_button" class="blue-bg">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'save_w.svg' ?>" alt="Create Project" title="Create Project"
                            height="20">
                        <h3>Create Project</h3>
                    </div>
                </button>
            </header>

            <form id="project_form" class="flex-col" method="POST" action="">

                <!-- Info -->
                <fieldset id="info_section" class="flex-col content-section-block">
                    <div class="fieldset-title text-w-icon start">
                        <img src="<?= ICON_PATH . 'project_w.svg' ?>" alt="Info" title="Info" height="28">
                        <span class="flex-col flex-child-start-h">
                            <h2>Project Info</h2>
                            <p class="light-text">Enter the basic details of your project.</p>
                        </span>
                    </div>

                    <section class="inputs-section flex-col">

                        <!-- NAME -->
                        <div class="input-rules-container">
                            <div class="input-label-container">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Name" title="Name" height="24">
                                    <label for="name">Name</label>
                                </div>
                                <input type="text" name="name" id="name" placeholder="(eg. Project Management System)"
                                    value="<?= $projectData['name'] ?>" min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>"
                                    autocapitalize="on" autocomplete="on" required>
                            </div>

                            <?= workNameRules() ?>
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="input-rules-container">
                            <div class="input-label-container">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Description"
                                        title="Description" height="24">
                                    <label for="description">Description</label>
                                </div>
                                <textarea name="description" id="description" rows="4"
                                    placeholder="Describe what your project objectives, scope, and deliverables (optional)"
                                    min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>" autocapitalize="on"
                                    autocomplete="on" required><?= $projectData['description'] ?></textarea>
                            </div>

                            <?= workDescriptionRules() ?>
                        </div>

                        <section class="row-inputs flex-row">

                            <!-- BUDGET -->
                            <div class="input-rules-container">
                                <div class="input-label-container">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'budget_w.svg' ?>" alt="Budget" title="Budget"
                                            height="24">
                                        <label for="budget">Budget</label>
                                    </div>
                                    <div class="input-w-prefix">
                                        <span class="input-prefix">₱</span>
                                        <input type="number" name="budget" id="budget" placeholder="0.00" value="<?= $projectData['budget'] ?>" required>
                                    </div>
                                </div>

                                <?= workBudgetRules() ?>
                            </div>

                            <!-- MAX WORKERS -->
                            <div class="input-rules-container">
                                <div class="input-label-container">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Max Workers"
                                            title="Max Workers" height="24">
                                        <label for="max_workers">Max Workers</label>
                                    </div>
                                    <input type="number" name="max_workers" id="max_workers"
                                        placeholder="Define the maximum number of workers (eg. 10)" value="<?= $projectData['maxWorkers'] ?>"
                                        min="<?= WORKER_COUNT_MIN ?>" max="<?= WORKER_COUNT_MAX ?>"
                                        value="<?= WORKER_COUNT_MIN ?>" required>
                                </div>

                                <?= workWorkerCountRules() ?>
                            </div>
                        </section>

                        <section class="row-inputs flex-row">

                            <!-- START DATE -->
                            <div class="input-rules-container">
                                <div class="input-label-container">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date"
                                            height="24">
                                        <label for="start_date_time">Start Date</label>
                                    </div>
                                    <input type="date" name="start_date_time" id="start_date_time"
                                        value="<?= $projectData['startDateTime'] ?>"
                                        min="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>" required>
                                </div>

                                <?= workStartDateTimeRules() ?>
                            </div>

                            <!-- COMPLETION DATE -->
                            <div class="input-rules-container">
                                <div class="input-label-container">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completion Date"
                                            title="Completion Date" height="24">
                                        <label for="completion_date_time">End Date</label>
                                    </div>
                                    <input type="date" name="completion_date_time" id="completion_date_time" value="<?= $projectData['completionDateTime'] ?>"
                                        min="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>" required>
                                </div>

                                <?= workCompletionDateTimeRules() ?>
                            </div>
                        </section>

                    </section>
                </fieldset>

                <!-- Phases -->
                <fieldset id="phase_section" class="flex-col content-section-block">
                    <div class="fieldset-title text-w-icon start">
                        <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="Phases" title="Phases" height="28">
                        <span class="flex-col flex-child-start-h">
                            <h2>Phases</h2>
                            <p class="light-text">Break down your projects into manageable phases</p>
                        </span>
                    </div>

                    <!-- Existing Phases (If any) -->
                    <?php foreach ($projectData['phases'] as $phase) {
                        echo phaseFormCard($phase);
                    } ?>

                    <div class="no-phases-wall no-content-wall <?= $uiState['phaseNoWall'] ?>">
                        <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No Phases Found" title="No Phases Found"
                            height="100">
                        <span>
                            <h3>No Phases Added</h3>
                            <p>Please add phases to organize your project effectively.</p>
                        </span>
                    </div>

                    <button id="add_phase_button" class="transparent-bg" type="button">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Phase" title="Add Phase" height="24">
                            <h3>Add Phase</h3>
                        </div>
                    </button>
                </fieldset>

                <!-- Workers -->
                <fieldset id="workers_section" class="flex-col content-section-block">
                    <div class="fieldset-title text-w-icon start">
                        <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Workers" title="Workers" height="28">
                        <span class="flex-col flex-child-start-h">
                            <h2>Workers</h2>
                            <p class="light-text">Select workers from the available pool and set their default rates.
                            </p>
                        </span>
                    </div>

                    <section class="flex-row">

                        <!-- Worker Pool -->
                        <section class="worker-pool">
                            <section class="heading">
                                <div class="search-bar">
                                    <?= searchBar() ?>
                                </div>

                                <p class="light-text">Select workers to assign to this project.</p>
                            </section>

                            <section class="worker-pool-listing">
                                <ul class="list">
                                    <?php foreach ($projectData['workers'] as $worker) {
                                        echo workerPoolCard($worker);
                                    } ?>
                                </ul>

                                <div class="no-workers-wall no-content-wall <?= $uiState['workerNoWall'] ?>">
                                    <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No Workers Found
                                    title=" No Workers Found" height="80">
                                    <span>
                                        <h3>No Workers Found</h3>
                                        <p>Try adjusting your search to find workers.</p>
                                    </span>
                                </div>
                            </section>

                        </section>

                        <section class="selected-workers-table flex-col">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Worker Name</th>
                                        <th>Default Rate (₱/hr)</th>
                                        <th class="center-text">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <!-- Selected workers will be added here dynamically -->
                                    <?php foreach ($projectData['workers'] as $worker) {
                                        echo selectedWorkerRow($worker);
                                    } ?>
                                </tbody>
                            </table>

                            <div class="no-workers-wall no-content-wall <?= $uiState['workerNoWall'] ?>">
                                <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No Workers Selected"
                                    title="No Workers Selected" height="80">
                                <span>
                                    <h3>No Workers Selected</h3>
                                    <p>Select workers from the pool to assign them to this project.</p>
                                </span>
                            </div>
                        </section>

                    </section>
                </fieldset>

            </form>

        </section>

    </main>

    <script type="module" src="<?= EVENT_PATH . 'project-form' . DS . 'scroll-navigation.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'project-form' . DS . 'create-phase-form-card.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'project-form' . DS . 'validate-forms.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'project-form' . DS . 'worker' . DS . 'add.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'project-form' . DS . 'create' . DS . 'search-workers.js' ?>"
        defer></script>
    <script type="module" src="<?= EVENT_PATH . 'project-form' . DS . 'create' . DS . 'submit.js' ?>" defer></script>
</body>

</html>