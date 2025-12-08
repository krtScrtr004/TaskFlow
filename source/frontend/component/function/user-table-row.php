<?php

use App\Entity\User;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Enumeration\WorkerStatus;

/**
 * Renders a user/worker table row as an HTML string.
 *
 * This function builds a table row (<tr>) representing a User or Worker.
 * It performs necessary escaping and fallbacks, and conditionally renders
 * information for display in a table format.
 *
 * @param User|Worker $user The User or Worker object to render.
 *
 * @return string Rendered HTML for the user table row (escaped and safe for direct output).
 */
function userTableRow(User|Worker $user): string
{
    $name           = htmlspecialchars(createFullName($user->getFirstName(), $user->getMiddleName(), $user->getLastName()));
    $id             = htmlspecialchars(UUID::toString($user->getPublicId()));
    $email          = htmlspecialchars($user->getEmail());
    $contact        = htmlspecialchars($user->getContactNumber());
    $role           = htmlspecialchars($user->getRole()->getDisplayName());
    $profileLink    = htmlspecialchars($user->getProfileLink()) ?: ICON_PATH . 'profile_w.svg';

    $totalTasks     = htmlspecialchars($user->getAdditionalInfo('totalTasks') ?? 0);
    $completedTask  = htmlspecialchars($user->getAdditionalInfo('completedTasks') ?? 0);

    ob_start();
    ?>
    <tr class="user-table-row" data-userid="<?= $id ?>">
        <!-- Profile Picture -->
        <td>
            <img class="user-profile circle fit-cover" src="<?= $profileLink ?>" alt="<?= $name ?>" title="<?= $name ?>" loading="lazy" height="42" width="42">
        </td>

        <!-- Name -->
        <td>
            <strong class="user-name" title="<?= $name ?>"><?= $name ?></strong>
        </td>

        <!-- Role -->
        <td>
            <span class="user-role"><?= $role ?></span>
        </td>

        <!-- Email -->
        <td>
            <span class="user-email single-line-ellipsis" title="<?= $email ?>"><?= $email ?></span>
        </td>

        <!-- Contact -->
        <td>
            <span class="user-contact" title="<?= $contact ?>"><?= $contact ?></span>
        </td>

        <!-- Total Tasks -->
        <td class="center-text">
            <span class="user-total-tasks"><?= $totalTasks ?></span>
        </td>

        <!-- Completed Tasks -->
        <td class="center-text">
            <span class="user-completed-tasks"><?= $completedTask ?></span>
        </td>

        <?php if ($user instanceof Worker): ?>
            <!-- Worker Status -->
            <td>
                <?= WorkerStatus::badge($user->getStatus()) ?>
            </td>
        <?php endif; ?>
    </tr>
    <?php
    return ob_get_clean();
}

/**
 * Renders a table row for a Worker by delegating to userTableRow().
 *
 * @param Worker $worker Worker instance containing display data.
 *
 * @return string Rendered HTML for the worker table row.
 */
function workerTableRow(Worker $worker): string
{
    return userTableRow($worker);
}
