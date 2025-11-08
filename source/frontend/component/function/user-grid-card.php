<?php

use App\Entity\User;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Enumeration\WorkerStatus;
use App\Model\ProjectModel;
use App\Utility\WorkerPerformanceCalculator;

function userGridCard(User|Worker $user): string
{
    $name = htmlspecialchars($user->getFirstName() . ' ' . $user->getLastName());
    $id = htmlspecialchars(UUID::toString($user->getPublicId()));
    $email = htmlspecialchars($user->getEmail());
    $contact = htmlspecialchars($user->getContactNumber());
    $role = htmlspecialchars($user->getRole()->getDisplayName());
    $profileLink =
        htmlspecialchars($user->getProfileLink()) ?:
        ICON_PATH . 'profile_w.svg';

    $isUsersPage = strpos($_SERVER['REQUEST_URI'], 'users') !== false;

    ob_start();
    ?>
    <button class="user-grid-card unset-button" data-userid="<?= $id ?>">

        <!-- User Primary Info -->
        <section class="user-primary-info flex-row flex-child-center-h">
            <!-- User Profile Picture -->
            <img class="user-profile circle fit-cover" src="<?= $profileLink ?>" alt="<?= $name ?>" title="<?= $name ?>" loading="lazy" height="42">

            <div class="flex-col">
                <!-- Worker Name -->
                <h3 class="user-name start-text"><?= $name ?></h3>

                <!-- Worker ID -->
                <p class="user-id start-text"><em><?= $id ?></em></p>
            </div>
        </section>

        <?php if ($isUsersPage): ?>
            <div class="role-badge badge center-child white-bg">
                <p><strong class="user-role black-text"><?= $role ?></strong></p>
            </div>
        <?php endif; ?>

        <!-- Worker Statistics -->
        <section class="user-statistics flex-col">
            <?php if ($isUsersPage): 
                $totalTasks = htmlspecialchars($user->getAdditionalInfo('totalProjects') ?? 0);
                $completedProject = htmlspecialchars($user->getAdditionalInfo('completedProjects') ?? 0);
            ?>
                <!-- Total Projects -->
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'project_w.svg' ?>" alt="Total Project" title="Total Project" height="20">
                    <p>Total Projects: <?= $totalTasks ?></p>
                </div>

                <!-- Completed Projects -->
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completed Projects" title="Completed Projects" height="20">
                    <p>Completed Projects: <?= $completedProject ?></p>    
                </div>
            <?php else: 
                $totalTasks = htmlspecialchars($user->getAdditionalInfo('totalTasks') ?? 0);
                $completedTask = htmlspecialchars($user->getAdditionalInfo('completedTasks') ?? 0);
            ?>
                <!-- Total Tasks -->
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'task_w.svg' ?>" alt="Total Task" title="Total Task" height="20">
                    <p>Total Tasks: <?= $totalTasks ?></p>
                </div>

                <!-- Completed Tasks -->
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Total Task" title="Total Task" height="20">
                    <p>Completed Tasks: <?= $completedTask ?></p>
                </div>
            <?php endif; ?>
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