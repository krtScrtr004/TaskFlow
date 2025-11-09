<section id="add_phase_modal" class="modal-wrapper no-display">
    <div class="modal-form modal flex-col black-bg">
        <!-- Heading -->
        <section class="flex-row flex-space-between">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="Add Project Phase" title="Add Project Phase" height="20">
                <h3 class="title">Add Project Phase</h3>
            </div>

            <!-- Close Button -->
            <button id="add_phase_close_button" type="button" class="unset-button">
                <p class="red-text">✖</p>
            </button>
        </section>

        <hr>

        <form id="add_phase_form" class="flex-col" method="POST" action="">
            <!-- Name -->
            <div class="input-label-container">
                <label for="phase_name">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Phase Name"
                            title="Phase Name" height="20">

                        <p>Name</p>
                    </div>
                </label>

                <input type="text" name="phase_name" id="phase_name" min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>" placeholder="Phase Name" required>
            </div>

            <!-- Description -->
            <div class="input-label-container">
                <label for="phase_description">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Phase Description"
                            title="Phase Description" height="20">

                        <p>Description</p>
                    </div>
                </label>

                <textarea name="phase_description" id="phase_description" rows="4" min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>" placeholder="Phase Description (optional)"></textarea>
            </div>

            <div class="phase-secondary-info flex-row">
                <!-- Start Date -->
                <div class="input-label-container">
                    <label for="phase_start_datetime">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Phase Start Date"
                                title="Phase Start Date" height="20">

                            <p>Start Date</p>
                        </div>
                    </label>

                    <input type="date" name="phase_start_datetime" id="phase_start_datetime"
                        value="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>" required>
                </div>

                <!-- Completion Date -->
                <div class="input-label-container">
                    <label for="phase_completion_datetime">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Phase Completion Date"
                                title="Phase Completion Date" height="20">

                            <p>Completion Date</p>
                        </div>
                    </label>
                    
                    <input type="date" name="phase_completion_datetime" id="phase_completion_datetime"
                        value="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>" required>
                </div>
            </div>

            <!-- Add New Phase Button -->
            <button id="add_new_phase_button" type="button" class="blue-bg">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add New Phase" title="Add New Phase" height="20">
                    <h3 class="white-text">Add New Phase</h3>
                </div>
            </button>
        </form>
    </div>
</section>