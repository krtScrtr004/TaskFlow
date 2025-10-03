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

$project->setStatus(WorkStatus::ON_GOING);
$projectStatus = $project->getStatus();
$projectPhases = $project->getPhases();

$projectHasStarted = $projectStatus === WorkStatus::PENDING ? '' : 'disabled';
$projectIsCompleted = $projectStatus === WorkStatus::COMPLETED
    || $projectStatus === WorkStatus::CANCELLED
    || $projectStatus === WorkStatus::DELAYED
    ? 'disabled' : '';

include_once COMPONENT_PATH . 'template/add-phase-modal.php';
?>

<!-- Edit Project -->
<section class="edit-project flex-col">

    <!-- Heading -->
    <section class="heading">
        <?php if ($projectStatus === WorkStatus::COMPLETED || $projectStatus === WorkStatus::CANCELLED): ?>
            <div class="cannot-edit-warning">
                <p class="white-text">Editing unavailable. Project has been completed or cancelled.</p>
            </div>
        <?php endif; ?>

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
        <fieldset class="main-details-field flex-col" <?= $projectIsCompleted ?>>

            <!-- Project Description -->
            <div class="input-label-container">
                <label for="project_description">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'description_b.svg' ?>" alt="Project Description"
                            title="Project Description" height="20">

                        <p>Description</p>
                    </div>
                </label>
                <textarea name="project-description" id="project_description" rows="5"
                    cols="10"><?= $projectDescription ?></textarea>
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
                        max="9999999999" <?= $projectHasStarted ?> required>
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
                        value="<?= $projectStartDate ?>" <?= $projectHasStarted ?> required>
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
                        value="<?= $projectCompletionDate ?>" required>
                </div>
            </div>

        </fieldset>

        <hr>

        <!-- Phase Details -->
        <fieldset class="phase-details flex-col" <?= $projectIsCompleted ?>>
            <!-- Heading -->
            <section class="heading">
                <h3>Project Phases</h3>
                <p>Modify the details of each phase as needed.</p>
            </section>

            <section class="phases flex-col">

                <?php
                foreach ($projectPhases as $phase):
                    $phaseId = htmlspecialchars($phase->getId());
                    $phaseName = htmlspecialchars($phase->getName());
                    $phaseDescription = htmlspecialchars($phase->getDescription());

                    $phaseStartDate = $phase->getStartDateTime()->format('Y-m-d');
                    $phaseCompletionDate = $phase->getCompletionDateTime()->format('Y-m-d');
                    $phaseStatus = $phase->getStatus();

                    $phaseHasStarted = $phaseStatus === WorkStatus::PENDING ? '' : 'disabled';
                    $phaseIsCompleted = $phaseStatus === WorkStatus::COMPLETED
                        || $phaseStatus === WorkStatus::CANCELLED 
                        || $phaseStatus === WorkStatus::DELAYED
                        ? 'disabled' : '';
                ?>
                    <section class="phase" data-id="<?= $phaseId ?>">

                        <!-- Phase Name -->
                        <div class="flex-row flex-child-center-h flex-space-between">
                            <div class="flex-col">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'phase_b.svg' ?>" alt="<?= $phaseName ?>" title="<?= $phaseName ?>"
                                        height="22">

                                    <h3 class="phase-name wrap-text"><?= $phaseName ?></h3>
                                </div>

                                <?= WorkStatus::badge($phaseStatus) ?>
                            </div>

                            <?php if ($phaseStatus === WorkStatus::PENDING || $phaseStatus === WorkStatus::ON_GOING): ?>
                                <button type="button" class="unset-button">
                                    <img src="<?= ICON_PATH . 'delete_r.svg' ?>" alt="Cancel <?= $phaseName ?>" title="Cancel <?= $phaseName ?>" height="20">
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="phase-details-form flex-col">

                            <!-- Phase Description -->
                            <div class="input-label-container">
                                <label for="phase_description">
                                    <label for="<?= $phaseName ?>_description">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'description_b.svg' ?>" alt="Project Description"
                                                title="Project Description" height="20">

                                            <p>Description</p>
                                        </div>
                                    </label>
                                </label>
                                <textarea class="phase-description" name="<?= $phaseName ?>_description" id="<?= $phaseName ?>_description" rows="5"
                                    cols="10" <?= $phaseIsCompleted ?>><?= $phaseDescription ?></textarea>
                            </div>

                            <!-- Phase Secondary Info -->
                            <div class="phase-secondary-info flex-row">

                                <!-- Start Date -->
                                <div class="input-label-container">
                                    <label for="<?= $phaseName ?>_start_date">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'start_b.svg' ?>" alt="Project Start Date"
                                                title="Project Start Date" height="20">

                                            <p>Start Date</p>
                                        </div>
                                    </label>
                                    <input type="date" class="phase-start-datetime" name="<?= $phaseName ?>_start_date" id="<?= $phaseName ?>_start_date"
                                        value="<?= $phaseStartDate ?>" <?= $phaseHasStarted ?> required>
                                </div>

                                <!-- Completion Date -->
                                <div class="input-label-container">
                                    <label for="<?= $phaseName ?>_completion_date">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'complete_b.svg' ?>" alt="Project Completion Date"
                                                title="Project Completion Date" height="20">

                                            <p>Completion Date</p>
                                        </div>
                                    </label>
                                    <input type="date" class="phase-completion-datetime" name="<?= $phaseName ?>_completion_date" id="<?= $phaseName ?>_completion_date"
                                        value="<?= $phaseCompletionDate ?>" <?= $phaseIsCompleted ?> required>
                                </div>
                            </div>

                        </div>
                    </section>
                <?php endforeach; ?>

            </section>

            <button id="add_phase_button" type="button" class="transparent-bg">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'add_b.svg' ?>" alt="Add Phase" title="Add Phase" height="20">
                    <h3 class="black-text">Add Phase</h3>
                </div>
            </button>

        </fieldset>

        <!-- Save Button -->
        <?php if ($projectStatus !== WorkStatus::COMPLETED && $projectStatus !== WorkStatus::CANCELLED): ?>
            <div>
                <button id="save_project_info_button" type="button" class="center-child float-right blue-bg white-text"
                    <?= $projectIsCompleted ?>>
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'save_w.svg' ?>" alt="Save Project Info" title="Save Project Info"
                            height="20">

                        <h3 class="white-text">Save Info</h3>
                    </div>
                </button>
            </div>
        <?php endif; ?>

    </form>
</section>