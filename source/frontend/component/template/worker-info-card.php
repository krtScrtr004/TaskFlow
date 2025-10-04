<!-- Modal Container -->
<section id="worker_info_card_template" class="modal-wrapper no-display">
    <!-- Modal -->
    <div class="worker-info-card modal flex-col white-bg">
        <!-- Primary Info -->
        <section class="primary-info flex-row flex-space-between">
            <div class="flex-row flex-child-center-v">
                <img class="worker-profile-picture circle fit-contain" src="" alt="" height="60" width="60">

                <div class="flex-col">
                    <div class="flex-col flex-child-center-v">
                        <h4 class="worker-name"></h4>
                        <p class="worker-id">
                            <em></em>
                        </p>
                    </div>

                    <div class="worker-job-titles flex-row flex-wrap">
                        

                    </div>
                </div>

            </div>

            <div class="flex-col flex-child-start-v">
                <button id="worker_info_card_close_button" type="button" class="unset-button">
                    <p class="red-text">✖</p>
                </button>
            </div>
        </section>

        <!-- Worker Bio -->
            <p class="worker-bio">!</p>

        <hr>

        <!-- Worker Statistics -->
        <section class="worker-statistics-container flex-row">

            <!-- Total Tasks -->
            <div class="worker-total-tasks worker-statistic flex-col flex-child-center-h">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'task_b.svg' ?>" alt="Total Tasks" title="Total Tasks" height="20">
                    <p class="center-text">Total Tasks</p>
                </div>
                <h4 class="center-text"></h4>
            </div>

            <!-- Completed Tasks -->
            <div class="worker-completed-tasks worker-statistic flex-col flex-child-center-h">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'complete_b.svg' ?>" alt="Completed Tasks" title="Completed Tasks"
                        height="20">
                    <p class="center-text">Completed Tasks</p>
                </div>
                <h4 class="center-text"></h4>
            </div>

            <!-- Performance -->
            <div class="worker-performance worker-statistic flex-col flex-child-center-h">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'progress_b.svg' ?>" alt="Performance" title="Performance" height="20">
                    <p class="center-text">Performance</p>
                </div>
                <h4 class="center-text"></h4>
            </div>
        </section>

        <hr>

        <!-- Contact Information -->
        <section class="contact-info flex-col">

            <!-- Email -->
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'email_b.svg' ?>" alt="Email" title="Email" height="24">

                <p class="worker-email wrap-text"></p>
            </div>

            <!-- Contact Number -->
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'contact_b.svg' ?>" alt="Contact" title="Contact" height="24">

                <p class="worker-contact wrap-text"></p>
            </div>
        </section>

        <!-- Terminate Worker Button -->
        <button id="terminate_worker_button" type="button" class="red-bg">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'close_w.svg' ?>" alt="Terminate" title="Terminate" height="20">

                <h3 class="white-text">Terminate</h3>
            </div>
        </button>
    </div>
</section>