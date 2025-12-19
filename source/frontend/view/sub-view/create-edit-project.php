<?php
// TODO: Fix table width on smaller screens

use App\Entity\User;

$uiState = [];
$projectData = [];

if ($project) {
    $uiState['pageName'] = 'Edit Project';
} else {
    $uiState['pageName'] = 'Create Project';
}

include_once COMPONENT_PATH . 'function' . DS . 'selected-worker-row.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

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
                        <div class="input-label-container">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Name" title="Name" height="24">
                                <label for="name">Name</label>
                            </div>
                            <input type="text" name="name" id="name" placeholder="(eg. Project Management System)"
                                min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>" autocapitalize="on" autocomplete="on"
                                required>
                        </div>

                        <div class="input-label-container">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Description" title="Description"
                                    height="24">
                                <label for="description">Description</label>
                            </div>
                            <textarea name="description" id="description" rows="4"
                                placeholder="Describe what your project objectives, scope, and deliverables (optional)"
                                min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>" autocapitalize="on"
                                autocomplete="on" required></textarea>
                        </div>

                        <section class="row-inputs flex-row">
                            <div class="input-label-container">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'budget_w.svg' ?>" alt="Budget" title="Budget"
                                        height="24">
                                    <label for="budget">Budget</label>
                                </div>
                                <div class="input-w-prefix">
                                    <span class="input-prefix">₱</span>
                                    <input type="number" name="budget" id="budget" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="input-label-container">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Max Workers" title="Max Workers"
                                        height="24">
                                    <label for="max_workers">Max Workers</label>
                                </div>
                                <input type="number" name="max_workers" id="max_workers"
                                    placeholder="Define the maximum number of workers (eg. 10)"
                                    min="<?= WORKER_COUNT_MIN ?>" max="<?= WORKER_COUNT_MAX ?>"
                                    value="<?= WORKER_COUNT_MIN ?>" required>
                            </div>
                        </section>

                        <section class="row-inputs flex-row">
                            <div class="input-label-container">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date"
                                        height="24">
                                    <label for="start_date_time">Start Date</label>
                                </div>
                                <input type="date" name="start_date_time" id="start_date_time"
                                    value="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>" required>
                            </div>

                            <div class="input-label-container">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completion Date"
                                        title="Completion Date" height="24">
                                    <label for="completion_date_time">End Date</label>
                                </div>
                                <input type="date" name="completion_date_time" id="completion_date_time"
                                    value="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>" required>
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

                    <?php include_once COMPONENT_PATH . 'template' . DS . 'phase-form-card.php' ?>

                    <div class="no-phases-wall no-content-wall flex-col">
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
                                <div class="search-bar input-w-suffix">
                                    <input type="text" id="worker_search_input" placeholder="Search workers..."
                                        autocomplete="off">
                                    <button type="button" id="worker_search_button" class="transparent-bg">
                                        <img src="<?= ICON_PATH . 'search_w.svg' ?>" alt="Search" title="Search"
                                            height="20">
                                    </button>
                                </div>

                                <p class="light-text">Select workers to assign to this project.</p>
                            </section>

                            <section class="worker-pool-listing">
                                <ul class="list">
                                    <?php for ($i = 0; $i < 10; $i++): ?>
                                        <li>
                                            <button class="worker-pool-card unset-button" type="button">
                                                <img src="<?= ICON_PATH . 'profile_w.svg' ?>" class="circle fit-cover"
                                                    alt="" height="55">

                                                <div class="flex-col flex-child-start-h worker-info">
                                                    <span class="name">John Doe</span>
                                                    <div class="flex-row flex-wrap">
                                                        <span class="role-chip chip badge light-text">Developer</span>
                                                    </div>
                                                </div>
                                            </button>
                                        </li>
                                    <?php endfor; ?>
                                </ul>

                                <div class="no-workers-wall no-content-wall flex-col">
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
                                        <th>Role</th>
                                        <th>Default Rate (₱)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <!-- Selected workers will be added here dynamically -->
                                    <?php //selectedWorkerRow(User::createPartial([])) ?>
                                </tbody>
                            </table>

                            <div class="no-workers-wall no-content-wall flex-col">
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

    <script type="module" src="<?= EVENT_PATH . 'project-form/scroll-navigation.js' ?>" defer></script>
</body>

</html>