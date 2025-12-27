<?php use App\Dependent\Worker; ?>

<section id="add_worker_table" class="modal-wrapper no-display">

    <div class="add-worker-table modal flex-col light-black-bg">
        <section class="heading flex-row flex-space-between">
            <h3>Specify Worker's Hourly Default Rate</h3>

            <div class="flex-col flex-child-start-v">
                <button id="add_worker_table_close_button" type="button" class="close-button unset-button">
                    <p class="red-text">✖</p>
                </button>
            </div>
        </section>

        <section class="table-container">
            <table class="no-display">

                <!-- Heading -->
                <thead>
                    <tr>
                        <th>Worker Name</th>
                        <th>Default Rate (₱/hr)</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <!-- Body -->
                <tbody>
                </tbody>

            </table>

            <div class="no-worker-wall no-content-wall flex-col full-body-content">
                <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No Workers Selected" title="No Workers Selected" height="75">
                <span>
                    <h3>No Workers Selected</h3>
                    <p>Added workers will appear here</p>
                </span>
            </div>
        </section>

        <section class="buttons flex-row flex-child-end-v">

            <!-- Add More Button -->
            <button id="add_more_worker_button" class="transparent-bg" type="button">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add More Worker" title="And More Worker" height="24">
                    <h3 class="white-text">Add</h3>
                </div>
            </button>

            <!-- Save Button -->
            <button id="save_added_worker_button" class="blue-bg" type="button">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'save_w.svg' ?>" alt="Save" title="Save" height="24">
                    <h3 class="white-text">Save</h3>
                </div>
            </button>

        </section>
    </div>

</section>