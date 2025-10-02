<!-- Modal Container -->
<section id="add_worker_modal_template" class="modal-wrapper no-display">

    <!-- Modal Content -->
    <div class="add-worker-modal modal flex-col white-bg">

        <!-- Heading -->
        <section class="heading">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'worker_b.svg' ?>" alt="Add Worker To Project" title="Add Worker To Project"
                    height="28">

                <h3 class="title">Add Worker To Project</h3>
            </div>

            <!-- Search Bar -->
            <form class="search-bar" action="" method="POST">
                <input type="text" name="search_worker" id="search_worker" placeholder="Search by Name or ID" min="1"
                    max="255" autocomplete="on" required>
                <button id="search_worker_button" type="button" class="transparent-bg">
                    <img src="<?= ICON_PATH . 'search_b.svg' ?>" alt="Search Worker" title="Search Worker" height="20">
                </button>
            </form>
        </section>

        <!-- Worker List -->
        <section class="worker-list flex-col"></section>

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