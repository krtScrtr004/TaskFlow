<?php

use App\Core\UUID;
use App\Enumeration\WorkStatus;
use App\Model\ProjectModel;

$projectId = $args['projectId'] ?? null;
if (!$projectId) {
    throw new ErrorException('Project ID is required to edit a project.');
}

// Fetch project data from the database using the provided project ID
$project = ProjectModel::findFull(
    UUID::fromString($projectId),
    ['phases' => true]
);
if (!$project) {
    throw new ErrorException('Project data is required to edit a project.');
}

$projectData = [
    'id'                => htmlspecialchars(UUID::toString($project->getPublicId())),
    'name'              => htmlspecialchars($project->getName()),
    'description'       => htmlspecialchars($project->getDescription()),
    'budget'            => htmlspecialchars($project->getBudget()),
    'startDate'         => htmlspecialchars(formatDateTime($project->getStartDateTime())),
    'completionDate'    => htmlspecialchars(formatDateTime($project->getCompletionDateTime())),
    'status'            => WorkStatus::PENDING,
    'phases'            => $project->getPhases()
];

// Prepare UI state flags
$uiState = [
    'projectHasStarted'     => $projectData['status'] === WorkStatus::PENDING ? '' : 'disabled',
    'projectIsCompleted'    => in_array($projectData['status'], [WorkStatus::COMPLETED, WorkStatus::CANCELLED]) ? 'disabled' : '',
    'canEdit'               => !in_array($projectData['status'], [WorkStatus::COMPLETED, WorkStatus::CANCELLED]),
    'showWarning'           => in_array($projectData['status'], [WorkStatus::COMPLETED, WorkStatus::CANCELLED])
];

require_once COMPONENT_PATH . 'template/add-phase-modal.php';
?>

<!-- Edit Project -->
<section class="edit-project flex-col">

    <!-- Heading -->
    <section class="heading">
        <?php if ($uiState['showWarning']): ?>
            <div class="cannot-edit-warning">
                <p class="white-text">Editing unavailable. Project has been completed or cancelled.</p>
            </div>
        <?php endif; ?>

        <div class="title flex-row flex-child-center-h">
            <div class="project-name text-w-icon">
                <img src="<?= ICON_PATH . 'project_w.svg' ?>" alt="<?= $projectData['name'] ?>" title="<?= $projectData['name'] ?>" height="40">
                <h1><?= $projectData['name'] ?></h1>
            </div>

            <p class="project-id">
                <em><?= $projectData['id'] ?></em>
            </p>
        </div>

        <p><?= $component['description'] ?></p>
    </section>

    <!-- Editable Details Form -->
    <form id="editable_project_details" class="flex-col" action="" method="POST" data-projectId="<?= $projectData['id'] ?>">

        <!-- Main Details Field -->
        <fieldset class="main-details-field flex-col" <?= $uiState['projectIsCompleted'] ?>>

            <!-- Project Description -->
            <div class="input-label-container">
                <label for="project_description">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Project Description" title="Project Description" height="20">
                        <p>Description</p>
                    </div>
                </label>

                <textarea name="project-description" id="project_description" rows="5" cols="10" min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>"><?= $projectData['description'] ?></textarea>
            </div>

            <!-- Project Secondary Info -->
            <div class="project-secondary-info flex-row">
                <!-- Project Budget -->
                <div class="input-label-container">
                    <label for="project_budget">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'budget_w.svg' ?>" alt="Project Budget" title="Project Budget" height="20">
                            <p>Budget</p>
                        </div>
                    </label>

                    <input type="number" name="project_budget" id="project_budget" value="<?= $projectData['budget'] ?>"
                        min="<?= BUDGET_MIN ?>" max="<?= BUDGET_MAX ?>" <?= $uiState['projectHasStarted'] ?> required>
                </div>

                <!-- Start Date -->
                <div class="input-label-container">
                    <label for="project_start_date">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Project Start Date" title="Project Start Date" height="20">
                            <p>Start Date</p>
                        </div>
                    </label>

                    <input type="date" name="project-start-date" id="project_start_date"
                        value="<?= $projectData['startDate'] ?>" <?= $uiState['projectHasStarted'] ?> required>
                </div>

                <!-- Completion Date -->
                <div class="input-label-container">
                    <label for="project_completion_date">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Project Completion Date" title="Project Completion Date" height="20">
                            <p>Completion Date</p>
                        </div>
                    </label>

                    <input type="date" name="project-completion-date" id="project_completion_date"
                        value="<?= $projectData['completionDate'] ?>" required>
                </div>
            </div>

        </fieldset>

        <hr>

        <!-- Phase Details -->
        <fieldset class="phase-details flex-col" <?= $uiState['projectIsCompleted'] ?>>
            <!-- Heading -->
            <section class="heading">
                <h3>Project Phases</h3>
                <p>Modify the details of each phase as needed.</p>
            </section>

            <section class="phases flex-col">
                <?php foreach ($projectData['phases'] as $phase):
                    $phaseData = [
                        'id'            => htmlspecialchars(UUID::toString($phase->getPublicId())),
                        'name'          => htmlspecialchars($phase->getName()),
                        'description'   => htmlspecialchars($phase->getDescription()),
                        'startDate'     => htmlspecialchars(formatDateTime($phase->getStartDateTime())),
                        'completionDate'=> htmlspecialchars(formatDateTime($phase->getCompletionDateTime())),
                        'status'        => $phase->getStatus()
                    ];

                    $phaseUiState = [
                        'hasStarted'    => $phaseData['status'] === WorkStatus::PENDING ? '' : 'disabled',
                        'isCompleted'   => in_array($phaseData['status'], [WorkStatus::COMPLETED, WorkStatus::CANCELLED]) ? 'disabled' : ''
                    ];
                ?>

                    <!-- Phases -->
                    <section class="phase" data-phaseid="<?= $phaseData['id'] ?>">

                        <!-- Phase Name -->
                        <div class="flex-row flex-child-center-h flex-space-between">
                            <div class="flex-col">
                                <div class="text-w-icon">
                                    <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="<?= $phaseData['name'] ?>" title="<?= $phaseData['name'] ?>"
                                        height="22">

                                    <h3 class="phase-name wrap-text"><?= $phaseData['name'] ?></h3>
                                </div>

                                <?= WorkStatus::badge($phaseData['status']) ?>
                            </div>

                            <!-- Delete Button -->
                            <?php if (in_array($phaseData['status'], [WorkStatus::PENDING, WorkStatus::ON_GOING])): ?>
                                <button type="button" class="cancel-phase-button unset-button">
                                    <img src="<?= ICON_PATH . 'delete_r.svg' ?>" alt="Cancel <?= $phaseData['name'] ?>" title="Cancel <?= $phaseData['name'] ?>" height="20">
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="phase-details-form flex-col">

                            <!-- Phase Description -->
                            <div class="input-label-container">
                                <label for="<?= $phaseData['name'] ?>_description">
                                    <div class="text-w-icon">
                                        <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Project Description"
                                            title="Project Description" height="20">

                                        <p>Description</p>
                                    </div>
                                </label>

                                <textarea class="phase-description" name="<?= $phaseData['name'] ?>_description" id="<?= $phaseData['name'] ?>_description" rows="5"
                                    cols="10" min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>" <?= $phaseUiState['isCompleted'] ?>><?= $phaseData['description'] ?></textarea>
                            </div>

                            <!-- Phase Secondary Info -->
                            <div class="phase-secondary-info flex-row">

                                <!-- Start Date -->
                                <div class="input-label-container">
                                    <label for="<?= $phaseData['name'] ?>_start_date">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Project Start Date"
                                                title="Project Start Date" height="20">

                                            <p>Start Date</p>
                                        </div>
                                    </label>

                                    <input type="date" class="phase-start-datetime" name="<?= $phaseData['name'] ?>_start_date" id="<?= $phaseData['name'] ?>_start_date"
                                        value="<?= $phaseData['startDate'] ?>" <?= $phaseUiState['hasStarted'] ?> required>
                                </div>

                                <!-- Completion Date -->
                                <div class="input-label-container">
                                    <label for="<?= $phaseData['name'] ?>_completion_date">
                                        <div class="text-w-icon">
                                            <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Project Completion Date"
                                                title="Project Completion Date" height="20">

                                            <p>Completion Date</p>
                                        </div>
                                    </label>
                                    
                                    <input type="date" class="phase-completion-datetime" name="<?= $phaseData['name'] ?>_completion_date" id="<?= $phaseData['name'] ?>_completion_date"
                                        value="<?= $phaseData['completionDate'] ?>" <?= $phaseUiState['isCompleted'] ?> required>
                                </div>
                            </div>

                        </div>
                    </section>
                <?php endforeach; ?>

            </section>

            <button id="add_phase_button" type="button" class="transparent-bg">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Phase" title="Add Phase" height="20">
                    <h3>Add Phase</h3>
                </div>
            </button>

        </fieldset>

        <!-- Save Button -->
        <?php if (!in_array($projectData['status'], [WorkStatus::COMPLETED, WorkStatus::CANCELLED])): ?>
            <div>
                <button id="save_project_info_button" type="submit" class="center-child float-right blue-bg white-text"
                    <?= $uiState['projectIsCompleted'] ?>>
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