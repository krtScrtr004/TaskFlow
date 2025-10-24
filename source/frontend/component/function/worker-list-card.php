<?php

use App\Dependent\Worker;

function workerListCard(Worker $worker): string
{
    $profileLink =
        htmlspecialchars($worker->getProfileLink()) ?:
        ICON_PATH . 'profile_w.svg';
    $name = htmlspecialchars($worker->getFirstName() . ' ' . $worker->getLastName());
    $id = htmlspecialchars($worker->getPublicId());
    $jobTitles = $worker->getJobTitles();

    ob_start();
    ?>
    <!-- Worker List Card -->
    <button class="worker-list-card unset-button" data-id="<?= $id ?>">
        <img src="<?= $profileLink ?>" alt="<?= $name ?>" title="<?= $name ?>" height="40">

        <div class="flex-col">

            <!-- Name and ID -->
            <div>
                <h4 class="wrap-text"><?= $name ?></h4>
                <p><em><?= $id ?></em></p>
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
