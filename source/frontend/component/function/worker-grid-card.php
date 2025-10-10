<?php

function workerGridCard(Worker $worker): string
{
    $name = htmlspecialchars($worker->getFirstName() . ' ' . $worker->getLastName());
    $id = htmlspecialchars($worker->getPublicId());
    $email = htmlspecialchars($worker->getEmail());
    $contact = htmlspecialchars($worker->getContactNumber());
    $profileLink =
        htmlspecialchars($worker->getProfileLink()) ?:
        ICON_PATH . 'profile_w.svg';
    $status = $worker->getStatus();

    // TODO: Fetch user tasks from the DB
    $performance = WorkerPerformanceCalculator::calculateWorkerPerformance(TaskModel::all());

    ob_start();
    ?>
    <button class="worker-grid-card unset-button" data-workerid="<?= $id ?>">

        <!-- Worker Primary Info -->
        <section class="worker-primary-info flex-row flex-child-center-h">
            <!-- Worker Profile Picture -->
            <img class="circle fit-contain" src="<?= $profileLink ?>" alt="<?= $name ?>"
                title="<?= $name ?>" height="32">

            <div class="flex-col">
                <!-- Worker Name -->
                <h3 class="worker-name start-text"><?= $name ?></h3>

                <!-- Worker ID -->
                <p class="worker-id start-text"><em><?= $id ?></em></p>
            </div>
        </section>

        <!-- Worker Statistics -->
        <section class="worker-statistics flex-col">
            <!-- Completed Tasks -->
            <p>Completed Tasks: <?= $performance['totalTasks'] ?></p>

            <!-- Performance -->
            <p>Performance: <?= $performance['overallScore'] ?>%</p>
        </section>

        <hr>

        <!-- Worker Contact Info -->
        <section class="worker-contact-info flex-col">
            <!-- Worker Email -->
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'email_w.svg' ?>" alt="Worker Email" title="Worker Email" height="20">
                <p>Email: <?= $email ?></p>
            </div>

            <!-- Contact Number -->
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'contact_w.svg' ?>" alt="Contact Number" title="Contact Number" height="20">
                <p>Contact: <?= $contact ?></p>
            </div>
        </section>

        <!-- Worker Status -->
        <section class="worker-status flex-col flex-child-end-h flex-child-end-v">
            <div><?= WorkerStatus::badge($status) ?></div>
        </section>

    </button>
    <?php
    return ob_get_clean();
}