<?php include_once COMPONENT_PATH . 'template/add-phase-modal.php' ?>

<!-- Create Project Form -->
<form id="create_project_form" class="create-project flex-col" action="" method="POST">

    <?= hiddenCsrfInput() ?>

    <!-- Project Details -->
    <fieldset class="project-details flex-col">
        <!-- Project Name -->
        <div class="input-label-container">
            <label for="project_name">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Project Name" title="Project Name" height="20">

                    <p>Project Name</p>
                </div>
            </label>
            <input type="text" name="project_name" id="project_name" placeholder="Enter project name"
                min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>" required>
        </div>

        <!-- Project Description -->
        <div class="input-label-container">
            <label for="project_description">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Project Description"
                        title="Project Description" height="20">
                    <p>Description</p>
                </div>
            </label>

            <textarea name="project-description" id="project_description" rows="5" cols="10" min="<?= LONG_TEXT_MIN ?>"
                max="<?= LONG_TEXT_MAX ?>" placeholder="(Optional) Type here..."></textarea>
        </div>

        <!-- Project Secondary Info -->
        <div class="project-secondary-info flex-row">
            <!-- Project Budget -->
            <div class="input-label-container">
                <label for="project_budget">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'budget_w.svg' ?>" alt="Project Budget" title="Project Budget"
                            height="20">
                        <p>Budget</p>
                    </div>
                </label>

                <input type="number" name="project-budget" id="project_budget" value="0" min="<?= BUDGET_MIN ?>" max="<?= BUDGET_MAX ?>"
                    required>
            </div>

            <!-- Start Date -->
            <div class="input-label-container">
                <label for="project_start_date">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Project Start Date" title="Project Start Date"
                            height="20">
                        <p>Start Date</p>
                    </div>
                </label>

                <input type="date" name="project-start-date" id="project_start_date"
                    value="<?= (new DateTime())->format('Y-m-d') ?>" required>
            </div>

            <!-- Completion Date -->
            <div class="input-label-container">
                <label for="project_completion_date">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Project Completion Date"
                            title="Project Completion Date" height="20">
                        <p>Completion Date</p>
                    </div>
                </label>
                
                <input type="date" name="project-completion-date" id="project_completion_date"
                    value="<?= (new DateTime())->format('Y-m-d') ?>" required>
            </div>
        </div>

    </fieldset>

    <hr>

    <!-- Phases Field -->
    <fieldset class="phase-details flex-col">

        <!-- Heading -->
        <section class="heading">
            <h3>Project Phases</h3>
            <p>Add phases to your project.</p>
        </section>

        <!-- Phases List -->
        <section class="phases flex-col">
            <div class="no-phases-wall no-content-wall flex-col">
                <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No Phases" title="No Phases" height="70">
                <h4>No Phases Added</h4>
            </div>
        </section>

        <!-- Add Phase Button -->
        <button id="add_phase_button" type="button" class="transparent-bg">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add Phase" title="Add Phase" height="20">
                <h3 class="white-text">Add Phase</h3>
            </div>
        </button>

    </fieldset>

    <!-- Submit Button -->
    <button id="submit_project_button" type="submit" class="blue-bg">
        <div class="text-w-icon">
            <img src="<?= ICON_PATH . 'save_w.svg' ?>" alt="Submit Project" title="Submit Project" height="20">
            <h3 class="white-text">Submit</h3>
        </div>
    </button>
</form>