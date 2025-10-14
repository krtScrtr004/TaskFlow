<!-- Modal Container -->
<section id="user_info_card_template" class="modal-wrapper no-display">
    <!-- Modal -->
    <div class="user-info-card modal flex-col black-bg">
        <!-- Primary Info -->
        <section class="primary-info flex-row flex-space-between">
            <div class="flex-row flex-child-center-v">
                <img class="user-profile-picture circle fit-contain" src="" alt="" height="60" width="60">

                <div class="flex-col">
                    <div class="flex-col flex-child-center-v">
                        <h4 class="user-name"></h4>
                        <p class="user-id">
                            <em></em>
                        </p>
                    </div>

                    <div class="user-job-titles flex-row flex-wrap">


                    </div>
                </div>

            </div>

            <div class="flex-col flex-child-start-v">
                <button id="user_info_card_close_button" type="button" class="unset-button">
                    <p class="red-text">✖</p>
                </button>
            </div>
        </section>

        <!-- user Bio -->
        <p class="user-bio">!</p>

        <hr>

        <!-- user Statistics -->
        <section class="user-statistics-container flex-row">

            <?php
            $isUsersPage = strpos($_SERVER['REQUEST_URI'], 'users') !== false;
            if ($isUsersPage): ?>
                <!-- Total Projects -->
                <div class="user-total-projects user-total-statistics user-statistic flex-col flex-child-center-h">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'project_w.svg' ?>" alt="Total Projects" title="Total Projects"
                            height="20">
                        <p class="center-text">Total Projects</p>
                    </div>
                    <h4 class="center-text"></h4>
                </div>

                <!-- Completed Projects -->
                <div class="user-completed-project user-completed-statistics user-statistic flex-col flex-child-center-h">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completed Projects" title="Completed Projects"
                            height="20">
                        <p class="center-text">Completed Projects</p>
                    </div>
                    <h4 class="center-text"></h4>
                </div>
            <?php else: ?>
                <!-- Total Tasks -->
                <div class="user-total-tasks user-total-statistics user-statistic flex-col flex-child-center-h">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Total Tasks" title="Total Tasks" height="20">
                        <p class="center-text">Total Tasks</p>
                    </div>
                    <h4 class="center-text"></h4>
                </div>

                <!-- Completed Tasks -->
                <div class="user-completed-tasks user-completed-statistics user-statistic flex-col flex-child-center-h">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completed Tasks" title="Completed Tasks"
                            height="20">
                        <p class="center-text">Completed Tasks</p>
                    </div>
                    <h4 class="center-text"></h4>
                </div>
            <?php endif; ?>

            <!-- Performance -->
            <div class="user-performance user-statistic flex-col flex-child-center-h">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'progress_w.svg' ?>" alt="Performance" title="Performance" height="20">
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
                <img src="<?= ICON_PATH . 'email_w.svg' ?>" alt="Email" title="Email" height="24">

                <p class="user-email wrap-text"></p>
            </div>

            <!-- Contact Number -->
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'contact_w.svg' ?>" alt="Contact" title="Contact" height="24">

                <p class="user-contact wrap-text"></p>
            </div>
        </section>

        <!-- Terminate user Button -->
        <button id="terminate_worker_button" type="button" class="red-bg">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'close_w.svg' ?>" alt="Terminate" title="Terminate" height="20">

                <h3 class="white-text">Terminate</h3>
            </div>
        </button>
    </div>
</section>