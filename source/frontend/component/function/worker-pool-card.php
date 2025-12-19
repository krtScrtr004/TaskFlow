<?php

use App\Core\UUID;
use App\Dependent\Worker;

function workerPoolCard(Worker $worker): bool|string
{
    $id             = htmlspecialchars(UUID::toString($worker->getPublicId()));
    $name           = htmlspecialchars(createFullName($worker->getFirstName(), $worker->getMiddleName(), $worker->getLastName()));
    $jobTitles      = $worker->getJobTitles();
    $profileLink    = htmlspecialchars($worker->getProfileLink()) ?? ICON_PATH . 'profile_w.svg';

    ob_start();
    ?>
    <li>
        <button class="worker-pool-card unset-button" type="button" data-workerid="<?= $id ?>">
            <img src="<?= $profileLink ?>" class="circle fit-cover" alt="" height="55">

            <div class="flex-col flex-child-start-h worker-info">
                <span class="name"><?= $name ?></span>
                <div class="flex-row flex-wrap">
                    <?php foreach ($jobTitles as $jobTitle): ?>
                        <span class="role-chip chip badge light-text"><?= htmlspecialchars($jobTitle) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </button>
    </li>
    <?php
    return ob_get_clean();

}