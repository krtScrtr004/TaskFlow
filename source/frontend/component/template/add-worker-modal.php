<!-- Modal Container -->
<section id="add_worker_modal_template" class="modal-wrapper no-display">
    <?= hiddenCsrfInput() ?>

    <!-- Modal Content -->
    <div class="add-worker-modal modal flex-col black-bg">

        <!-- Heading -->
        <section class="heading">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Add Worker To Project" title="Add Worker To Project"
                    height="28">

                <h3 class="title">Add Worker To Project</h3>
            </div>

            <!-- Search Bar -->
            <form class="search-bar" action="" method="POST">
                <div>
                    <input type="text" name="search_worker" id="search_worker" placeholder="Search by Name or ID"
                        min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>" autocomplete="on" required>
                    <button id="search_worker_button" type="submit" class="transparent-bg">
                        <img src="<?= ICON_PATH . 'search_w.svg' ?>" alt="Search Worker" title="Search Worker"
                            height="20">
                    </button>
                </div>
            </form>
        </section>

        <!-- Worker List -->
        <section class="worker-list flex-col">
            <div class="list flex-col">
                <!-- Worker Items go here -->
            </div>

            <div class="sentinel"></div>
        </section>

        <!-- No Workers Wall -->
        <div class="no-workers-wall no-content-wall no-display">
            <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No workers assigned" title="No workers assigned"
                height="70">
            <h3 class="center-text">No workers found.</h3>
        </div>

        <!-- Buttons -->
        <section class="buttons flex-row flex-child-end-v">

            <!-- Cancel Button -->
            <button id="cancel_add_worker_button" type="button" class="red-bg close-button">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'close_w.svg' ?>" alt="Cancel" title="Cancel" height="20">
                    <h3 class="white-text">Cancel</>
                </div>
            </button>

            <!-- Add Button -->
            <button id="confirm_add_worker_button" type="button" class="blue-bg">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add" title="Add" height="20">
                    <h3 class="white-text">Add</h3>
                </div>
            </button>
        </section>

    </div>

</section>