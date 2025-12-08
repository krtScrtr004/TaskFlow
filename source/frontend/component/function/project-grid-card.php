<?php

use App\Entity\Project;
use App\Core\UUID;
use App\Enumeration\WorkStatus;

/**
 * Renders an HTML "project grid card" for the given Project and returns it as a string.
 *
 * The function builds a self-contained card containing primary project info (name and schedule),
 * a description and a status badge. It performs necessary conversions and HTML escaping to
 * produce safe output for embedding in templates:
 * - Converts the project's public ID to a string using UUID::toString()
 * - Escapes all visible text with htmlspecialchars()
 * - Converts start and completion datetimes to human-readable words using dateToWords()
 * - Falls back to 'No description provided' when description is null/empty
 * - Renders the project status using WorkStatus::badge()
 * - Constructs a link URL using REDIRECT_PATH . 'project' . DS . $id and uses ICON_PATH for the icon
 *
 * Note: The returned string contains HTML markup and may include HTML returned by WorkStatus::badge().
 *
 * @param Project $project Project domain object. The method expects the following accessors to be available:
 *      - getPublicId(): mixed   Public identifier convertible via UUID::toString()
 *      - getName(): string     Project name (will be escaped)
 *      - getDescription(): string|null   Project description (will be escaped; 'No description provided' if null/empty)
 *      - getStartDateTime(): mixed      Start datetime understood by dateToWords()
 *      - getCompletionDateTime(): mixed Completion datetime understood by dateToWords()
 *      - getStatus(): mixed   Status value accepted by WorkStatus::badge()
 *
 * @return string HTML string representing the project grid card
 */
function projectGridCard(Project $project): string
{
    $id                 = htmlspecialchars(UUID::toString($project->getPublicId()));
    $name               = htmlspecialchars($project->getName());
    $description        = htmlspecialchars($project->getDescription()) ?? 'No description provided';
    $startDateTime      = htmlspecialchars(
        dateToWords($project->getStartDateTime())
    );
    $completionDateTime = htmlspecialchars(
        dateToWords($project->getCompletionDateTime())
    );
    $status             = $project->getStatus();

    ob_start();
    ?>
    <div class="project-grid-card grid-card">
        <a class="full-body-content flex-col" href="<?= REDIRECT_PATH . 'project' . DS . $id ?>">

            <!-- Primary Info -->
            <section class="project-primary-info">
                <!-- Name -->
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'project_w.svg' ?>" alt="Project Name" title="Project Name" height="24">
                    <h3 class="project-name single-line-ellipsis" title="<?= $name ?>"><?= $name ?></h3>
                </div>

                <!-- Schedule -->
                <div class="project-schedule flex-row">
                    <?= $startDateTime . ' - ' . $completionDateTime ?>
                </div>
            </section>

            <!-- Description -->
            <p class="project-description multi-line-ellipsis-7" title="<?= $description ?>"><?= $description ?></p>

            <!-- Status -->
            <div class="project-status flex-col flex-child-end-v">
                <?= WorkStatus::badge($status) ?>
            </div>
        </a>
    </div>
    <?php
    return ob_get_clean();
}