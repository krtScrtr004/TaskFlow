<?php

use App\Core\UUID;
use App\Dependent\Worker;

/**
 * Renders a worker pool list item card as an HTML string.
 *
 * This function builds an <li> containing a button that represents a worker.
 * It sanitizes the worker's public id, full name and profile link using
 * htmlspecialchars, resolves the worker's profile image (falling back to
 * ICON_PATH . 'profile_w.svg' when none is provided), and captures the
 * generated markup via output buffering. The resulting button includes a
 * data-workerid attribute set to the worker's public UUID string and a
 * circular profile image with the worker's display name.
 *
 * @param Worker $worker Worker entity to render. Expected to provide:
 *      - getPublicId(): mixed Public UUID identifier
 *      - getFirstName(): string First name
 *      - getMiddleName(): string|null Middle name
 *      - getLastName(): string Last name
 *      - getProfileLink(): string|null URL to profile image
 *
 * @return bool|string HTML string of the rendered <li> on success, false on failure
 */
function workerPoolCard(Worker $worker): bool|string
{
    $id             = htmlspecialchars(UUID::toString($worker->getPublicId()));
    $name           = htmlspecialchars(createFullName($worker->getFirstName(), $worker->getMiddleName(), $worker->getLastName()));
    $profileLink    = $worker->getProfileLink() ? 
        htmlspecialchars($worker->getProfileLink()) 
        : ICON_PATH . 'profile_w.svg';

    ob_start();
    ?>
    <li>
        <button class="worker-pool-card unset-button" type="button" data-workerid="<?= $id ?>">
            <img src="<?= $profileLink ?>" class="circle fit-cover" alt="" height="55">

            <div class="flex-col flex-child-start-h worker-info">
                <span class="name"><?= $name ?></span>
            </div>
        </button>
    </li>
    <?php
    return ob_get_clean();

}