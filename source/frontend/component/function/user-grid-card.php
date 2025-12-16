<?php

use App\Entity\User;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Enumeration\WorkerStatus;
use App\Model\ProjectModel;

/**
 * Renders a user/worker "grid card" as an HTML string.
 *
 * This function builds a compact card (button element) representing a User or Worker.
 * It performs necessary escaping and fallbacks, and conditionally renders different
 * pieces of information depending on the current request context and the concrete
 * type of the provided object.
 *
 * Behavior and transformations:
 * - Escapes user-supplied values using htmlspecialchars for safety (name, id, email, contact, role, numeric stats).
 * - Converts the public id to a string via UUID::toString($user->getPublicId()) before escaping.
 * - Builds a full display name using createFullName(...) before escaping.
 * - Uses the user's profile link if present; otherwise falls back to ICON_PATH . 'profile_w.svg'.
 * - Detects "users" page context by checking strpos($_SERVER['REQUEST_URI'], 'users') !== false.
 *   - When on the users page: renders a role badge and "Total Projects" / "Completed Projects".
 *   - Otherwise: renders "Total Tasks" / "Completed Tasks".
 * - Retrieves numeric statistics via $user->getAdditionalInfo(...) and defaults missing values to 0.
 * - Includes contact/email lines with appropriate icons pulled from ICON_PATH constants.
 * - If $user is an instance of Worker, appends a status badge using WorkerStatus::badge($user->getStatus()).
 * - Loads the profile image with loading="lazy" and renders the card inside an output buffer (ob_start/ob_get_clean).
 *
 * Notes / side effects:
 * - Relies on global/server state: $_SERVER['REQUEST_URI'].
 * - Uses external helpers/constants/classes: createFullName(), UUID, ICON_PATH, WorkerStatus.
 * - The returned string is a complete HTML fragment (<button>...</button>) ready for output.
 *
 * @param User|Worker $user The User or Worker object to render. Expected methods used:
 *      - getFirstName(): string
 *      - getMiddleName(): string
 *      - getLastName(): string
 *      - getPublicId(): mixed (consumable by UUID::toString)
 *      - getEmail(): string
 *      - getContactNumber(): string
 *      - getRole(): object with getDisplayName(): string
 *      - getProfileLink(): string|null
 *      - getAdditionalInfo(string $key): mixed
 *      - (if Worker) getStatus(): mixed
 *
 * @return string Rendered HTML for the user grid card (escaped and safe for direct output).
 */
function userGridCard(User|Worker $user): string
{
    $name           = htmlspecialchars(createFullName($user->getFirstName(), $user->getMiddleName(), $user->getLastName()));
    $id             = htmlspecialchars(UUID::toString($user->getPublicId()));
    $email          = htmlspecialchars($user->getEmail());
    $contact        = htmlspecialchars($user->getContactNumber());
    $role           = htmlspecialchars($user->getRole()->getDisplayName());
    $profileLink    =
        htmlspecialchars($user->getProfileLink()) ?:
        ICON_PATH . 'profile_w.svg';

    $isUsersPage = strpos($_SERVER['REQUEST_URI'], 'users') !== false;

    ob_start();
    ?>
    <div class="user-grid-card grid-card" data-userid="<?= $id ?>">

        <!-- User Primary Info -->
        <section class="user-primary-info flex-row flex-child-center-h">
            <!-- User Profile Picture -->
            <img class="user-profile circle fit-cover" src="<?= $profileLink ?>" alt="<?= $name ?>" title="<?= $name ?>" loading="lazy" height="42">

            <div class="flex-col">
                <!-- Worker Name -->
                <h3 class="user-name start-text single-line-ellipsis" title="<?= $name ?>"><?= $name ?></h3>

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
                <p class="single-line-ellipsis" title="<?= $email ?>">Email: <?= $email ?></p>
            </div>

            <!-- Contact Number -->
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'contact_w.svg' ?>" alt="Contact Number" title="Contact Number" height="20">
                <p class="single-line-ellipsis" title="<?= $contact ?>">Contact: <?= $contact ?></p>
            </div>
        </section>

        <?php if ($user instanceof Worker): ?>
            <!-- Worker Status -->
            <section class="user-status flex-col flex-child-end-h flex-child-end-v">
                <div><?= WorkerStatus::badge($user->getStatus()) ?></div>
            </section>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders a grid card for a Worker by delegating to userGridCard().
 *
 * This is a thin, type-safe wrapper that accepts a Worker domain/entity object
 * and returns the rendered markup/string representation suitable for display
 * in a user/worker grid. Internally this function forwards the Worker instance
 * to userGridCard() to produce the output.
 *
 * @param Worker $worker Worker instance containing display data with common accessible properties:
 *      - id: int|null Worker ID
 *      - publicId: string|UUID|null Public identifier
 *      - firstName: string Worker's first name
 *      - middleName: string|null Worker's middle name
 *      - lastName: string Worker's last name
 *      - jobTitle: string|null Worker's primary job title
 *      - avatarUrl: string|null URL of worker's avatar or profile image
 *      - contactNumber: string|null Contact phone number
 *      - email: string|null Email address
 *      - profileLink: string|null URL to worker's profile or detail page
 *      - metadata: array|null Additional display metadata or attributes
 *
 * @return string Rendered HTML or markup for the worker grid card
 */
function workerGridCard(Worker $worker): string
{
    return userGridCard($worker);
}