<?php

use App\Core\UUID;
use App\Entity\User;

/**
 * Renders a compact user list card and returns it as an HTML string.
 *
 * This function:
 * - Builds a full display name using createFullName() from the user's name parts.
 * - Converts the user's public ID to a string with UUID::toString().
 * - Escapes all dynamic content with htmlspecialchars() to mitigate XSS.
 * - Uses the user's profile link if present; otherwise falls back to ICON_PATH . 'profile_w.svg'.
 * - Renders the user's job titles (iterable of strings) as individual "chip" elements.
 * - Wraps the markup in a <button> with a data-id attribute and includes a lazy-loaded <img>.
 * - Buffers output with output buffering (ob_start / ob_get_clean) and returns the generated markup.
 *
 * @param User $user User domain object providing the following accessors:
 *      - getFirstName(): string
 *      - getMiddleName(): string|null
 *      - getLastName(): string
 *      - getPublicId(): mixed (UUID or representation convertible via UUID::toString())
 *      - getJobTitles(): iterable<string> List/array of job title strings
 *      - getProfileLink(): string|null URL or path to profile image
 *
 * @return string HTML markup for a user list card (escaped and ready for output)
 */
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
                <h4 class="name wrap-text single-line-ellipsis" title="<?= $name ?>"><?= $name ?></h4>
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
