<?php
$projectId = $args['projectId'] ?? null;
if (!$projectId) {
    throw new ErrorException('Project ID is required to edit a project.');
}

// TODO: Fetch project data from the database using the provided project ID
$projects = ProjectModel::all();
$project = $projects[0];
if (!$project) {
    throw new ErrorException('Project data is required to edit a project.');
}

$projectId = htmlspecialchars($project->getId());
$projectName = htmlspecialchars($project->getName());
$projectDescription = htmlspecialchars($project->getDescription());

$projectBudget = htmlspecialchars(formatBudgetToPesos($project->getBudget()));
$projectStartDate = $project->getStartDateTime()->format('Y-m-d');
$projectCompletionDate = $project->getCompletionDateTime()->format('Y-m-d');

$projectHasStarted = $project->getStartDateTime() <= new DateTime() ? 'disabled' : '';
$projectIsCompleted = $project->getCompletionDateTime() < new DateTime() ? 'disabled' : '';

$projectPhases = $project->getPhases();
?>

<!-- Edit Project -->
<section class="edit-project flex-col">

    <!-- Heading -->
    <section class="heading">
        <div class="title flex-row flex-child-center-h">
            <div class="project-name text-w-icon">
                <img src="<?= ICON_PATH . 'project_b.svg' ?>" alt="<?= $projectName ?>" title="<?= $projectName ?>"
                    height="40">

                <h1><?= $projectName ?></h1>
            </div>

            <p class="project-id">
                <em><?= $projectId ?></em>
            </p>
        </div>

        <p><?= $component['description'] ?></p>
    </section>


    <!-- Editable Details Form -->
    <form id="editable_project_details" class="flex-col" action="" method="POST">

        <!-- Main Details Field -->
        <fieldset class="main-details-field flex-col">

            <!-- Project Description -->
            <div class="input-label-container">
                <label for="project_description">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'description_b.svg' ?>" alt="Project Description"
                            title="Project Description" height="20">

                        <p>Description</p>
                    </div>
                </label>
                <textarea name="project-description" id="project_description" rows="5" cols="10" <?= $projectHasStarted ?>><?= $projectDescription ?></textarea>
            </div>

            <!-- Project Secondary Info -->
            <div class="project-secondary-info flex-row">
                <!-- Project Budget -->
                <div class="input-label-container">
                    <label for="project_budget">
                        <label for="project_budget">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'budget_b.svg' ?>" alt="Project Budget" title="Project Budget"
                                    height="20">

                                <p>Budget</p>
                            </div>
                        </label>
                    </label>
                    <input type="number" name="project-budget" id="project_budget" value="<?= $projectBudget ?>" min="0"
                        max="9999999999" required <?= $projectIsCompleted ?>>
                </div>

                <!-- Start Date -->
                <div class="input-label-container">
                    <label for="project_start_date">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'start_b.svg' ?>" alt="Project Start Date"
                                title="Project Start Date" height="20">

                            <p>Start Date</p>
                        </div>
                    </label>
                    <input type="date" name="project-start-date" id="project_start_date"
                        value="<?= $projectStartDate ?>" required <?= $projectHasStarted ?>>
                </div>

                <!-- Completion Date -->
                <div class="input-label-container">
                    <label for="project_completion_date">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'complete_b.svg' ?>" alt="Project Completion Date"
                                title="Project Completion Date" height="20">

                            <p>Completion Date</p>
                        </div>
                    </label>
                    <input type="date" name="project-completion-date" id="project_completion_date"
                        value="<?= $projectCompletionDate ?>" required <?= $projectIsCompleted ?>>
                </div>
            </div>

        </fieldset>

        <hr>

        <!-- Phase Details -->
        <fieldset class="phase-details flex-col">
            <!-- Heading -->
            <section class="heading">
                <h3>Project Phases</h3>
                <p>Modify the details of each phase as needed.</p>
            </section>

            <?php
            foreach ($projectPhases as $phase):
                $phaseName = htmlspecialchars($phase->getName());
                $phaseDescription = htmlspecialchars($phase->getDescription());

                $phaseStartDate = $phase->getStartDateTime()->format('Y-m-d');
                $phaseCompletionDate = $phase->getCompletionDateTime()->format('Y-m-d');

                $phaseHasStarted = $phase->getStartDateTime() <= new DateTime() ? 'disabled' : '';
                $phaseIsCompleted = $phase->getCompletionDateTime() < new DateTime() ? 'disabled' : ''
                    ?>
                <section class="phase">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'phase_b.svg' ?>" alt="<?= $phaseName ?>" title="<?= $phaseName ?>"
                            height="20">

                        <h3 class="phase-name"><?= $phaseName ?></h3>
                    </div>

                    <div class="phase-details-form flex-col">

                        <!-- Phase Description -->
                        <div class="input-label-container">
                            <label for="phase_description">
                                <label for="project_description">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'description_b.svg' ?>" alt="Project Description"
                                            title="Project Description" height="20">

                                        <p>Description</p>
                                    </div>
                                </label>
                            </label>
                            <textarea name="phase-description" id="phase_description" rows="5" cols="10"
                                <?= $phaseIsCompleted ?>><?= $phaseDescription ?></textarea>
                        </div>

                        <!-- Phase Secondary Info -->
                        <div class="phase-secondary-info flex-row">

                            <!-- Start Date -->
                            <div class="input-label-container">
                                <label for="phase_start_date">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'start_b.svg' ?>" alt="Project Start Date"
                                            title="Project Start Date" height="20">

                                        <p>Start Date</p>
                                    </div>
                                </label>
                                <input type="date" name="phase-start-date" id="phase_start_date"
                                    value="<?= $phaseStartDate ?>" required <?= $phaseHasStarted ?>>
                            </div>

                            <!-- Completion Date -->
                            <div class="input-label-container">
                                <label for="phase_completion_date">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'complete_b.svg' ?>" alt="Project Completion Date"
                                            title="Project Completion Date" height="20">

                                        <p>Completion Date</p>
                                    </div>
                                </label>
                                <input type="date" name="phase-completion-date" id="phase_completion_date"
                                    value="<?= $phaseCompletionDate ?>" required <?= $phaseIsCompleted ?>>
                            </div>
                        </div>

                    </div>
                </section>
            <?php endforeach; ?>

        </fieldset>

        <!-- Save Button -->
        <button id="save_project_info_button" type="button" class="center-child float-right blue-bg white-text" <?= $projectIsCompleted ?>>
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'save_w.svg' ?>" alt="Save Project Info" title="Save Project Info"
                    height="20">

                <p class="white-text">Save Info</p>
            </div>
        </button>

    </form>
</section>