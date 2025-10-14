<?php

function userGridCard(User|Worker $user): string
{
    $name = htmlspecialchars($user->getFirstName() . ' ' . $user->getLastName());
    $id = htmlspecialchars($user->getPublicId());
    $email = htmlspecialchars($user->getEmail());
    $contact = htmlspecialchars($user->getContactNumber());
    $role = htmlspecialchars($user->getRole()->getDisplayName());
    $profileLink =
        htmlspecialchars($user->getProfileLink()) ?:
        ICON_PATH . 'profile_w.svg';

    $isUsersPage = strpos($_SERVER['REQUEST_URI'], 'users') !== false;

    $performance = WorkerPerformanceCalculator::calculate(ProjectModel::all());

    ob_start();
    ?>
    <button class="user-grid-card unset-button" data-userid="<?= $id ?>">

        <!-- User Primary Info -->
        <section class="user-primary-info flex-row flex-child-center-h">
            <!-- User Profile Picture -->
            <img class="circle fit-contain" src="<?= $profileLink ?>" alt="<?= $name ?>" title="<?= $name ?>" height="32">

            <div class="flex-col">
                <!-- Worker Name -->
                <h3 class="user-name start-text"><?= $name ?></h3>

                <!-- Worker ID -->
                <p class="user-id start-text"><em><?= $id ?></em></p>
            </div>
        </section>

        <?php if ($isUsersPage): ?>
            <div class="role-badge badge center-child white-bg">
                <p><strong class="black-text"><?= $role ?></strong></p>
            </div>
        <?php endif; ?>

        <!-- Worker Statistics -->
        <section class="user-statistics flex-col">
            <?php if ($isUsersPage):
                $projects = ProjectModel::all(); ?>
                <!-- Completed Projects -->
                <p>Completed Projects: <?= $performance['totalProjects'] ?></p>
            <?php else: ?>
                <!-- Completed Tasks -->
                <p>Completed Tasks: <?= $performance['totalTasks'] ?></p>
            <?php endif; ?>

            <!-- Performance -->
            <p>Performance: <?= $performance['overallScore'] ?>%</p>
        </section>

        <hr>

        <!-- Worker Contact Info -->
        <section class="user-contact-info flex-col">
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

        <?php if ($user instanceof Worker): ?>
            <!-- Worker Status -->
            <section class="user-status flex-col flex-child-end-h flex-child-end-v">
                <div><?= WorkerStatus::badge($user->getStatus()) ?></div>
            </section>
        <?php endif; ?>

    </button>
    <?php
    return ob_get_clean();
}

function workerGridCard(Worker $worker): string
{
    return userGridCard($worker);
}