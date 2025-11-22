<?php

use App\Core\UUID;
use App\Entity\User;

function userListCard(User $user): string
{
    $name           = htmlspecialchars(createFullName($user->getFirstName(), $user->getMiddleName(), $user->getLastName()));
    $id             = htmlspecialchars(UUID::toString($user->getPublicId()));
    $jobTitles      = $user->getJobTitles();
    $profileLink    =
        htmlspecialchars($user->getProfileLink()) ?:
        ICON_PATH . 'profile_w.svg';

    ob_start();
    ?>
    <!-- user List Card -->
    <button class="user-list-card unset-button" data-id="<?= $id ?>">
        <img class="circle fit-cover" src="<?= $profileLink ?>" alt="<?= $name ?>" title="<?= $name ?>" loading="lazy" height="40">

        <div class="flex-col">

            <!-- Name and ID -->
            <div>
                <h4 class="wrap-text"><?= $name ?></h4>
                <p><em class="id"><?= $id ?></em></p>
            </div>

            <div class="job-titles flex-row flex-wrap">
                <!-- Job Titles -->
                <?php foreach ($jobTitles as $jobTitle): ?>
                    <span class="job-title-chip">
                        <?= htmlspecialchars($jobTitle) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </button>
    <?php
    return ob_get_clean();
}
