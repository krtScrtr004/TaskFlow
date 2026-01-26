<?php

use App\Core\UUID;
use App\Entity\Phase;
use App\Enumeration\WorkStatus;

/**
 * Render an HTML "phase list" card for a given Phase.
 *
 * This function builds and returns a sanitized HTML fragment representing a
 * phase card used in the UI. It extracts values from the provided Phase
 * instance, converts/format values where required, and escapes output to help
 * prevent XSS.
 *
 * It performs the following transformations and lookups:
 * - Reads icon base path from the ICON_PATH constant and uses start_w.svg and
 *   complete_w.svg for schedule icons.
 * - Retrieves the phase name and description via $phase->getName() and
 *   $phase->getDescription(), and escapes them with htmlspecialchars().
 * - Retrieves start and completion datetimes via
 *   $phase->getStartDateTime() and $phase->getCompletionDateTime(), formats
 *   them with dateToWords(), then escapes the results.
 * - Retrieves the phase status via $phase->getStatus() and converts it to an
 *   HTML badge via WorkStatus::badge($status).
 * - Assembles the above pieces into an HTML HEREDOC string containing the
 *   structure and CSS classes used by the frontend (.phase-list-card,
 *   .phase-description, .project-schedule, etc.).
 *
 * Notes:
 * - The function assumes ICON_PATH constant, dateToWords() helper and
 *   WorkStatus::badge() are available in scope.
 * - Visible values are escaped with htmlspecialchars(); however callers should
 *   still ensure input Phase data is valid and trusted where appropriate.
 *
 * @param Phase $phase Phase domain object. Expected methods used:
 *      - getName(): string
 *      - getDescription(): string
 *      - getStartDateTime(): mixed (accepted by dateToWords)
 *      - getCompletionDateTime(): mixed (accepted by dateToWords)
 *      - getStatus(): mixed (accepted by WorkStatus::badge)
 *
 * @return string HTML string (sanitized) representing the phase card
 */
function phaseListCard(Phase $phase): string
{
    $ICON_PATH = ICON_PATH;

    $id                         = htmlspecialchars(UUID::toString($phase->getPublicId()));
    $name                       = htmlspecialchars($phase->getName());
    $description                = htmlspecialchars($phase->getDescription());
    $budget                     = htmlspecialchars(formatNumber($phase->getBudget()));
    $contingencyRate            = htmlspecialchars(
        formatNumber($phase->getContingencyRate()) . '%'
    );
    $budgetNote                 = $phase->getBudgetNote() ? htmlspecialchars($phase->getBudgetNote()) : null;
    $startDateTime              = htmlspecialchars(
        formatDateTime($phase->getStartDateTime(), 'd-m-Y')
    );
    $completionDateTime         = htmlspecialchars(
        formatDateTime($phase->getCompletionDateTime(), 'd-m-Y')
    );
    $actualCompletionDateTime  = htmlspecialchars(
        $phase->getActualCompletionDateTime()
            ? formatDateTime($phase->getActualCompletionDateTime(), 'd-m-Y')
            : 'N/A'
    );
    $status                     = $phase->getStatus();

    $statusBadge                = WorkStatus::badge($status);

    ob_start();
?>
    <!-- Phase Card -->
    <div class="phase-list-card flex-col black-bg">
        <section class="flex-col ">
            <div class="flex-row flex-space-between flex-child-center-h">
                <!-- Phase Name -->
                <h3 class="name"><?= $name ?></h3>


                <!-- Phase Status -->
                <div class="center-child">
                    <?= $statusBadge ?>
                </div>
            </div>

            <p class="id dark-white-text light-text"><?= $id ?></p>
        </section>

        <section class="">
            <!-- Project Description -->
            <p class="description dark-white-text light-text wrap-text"><?= $description ?></p>
        </section>

        <section class="secondary-info flex-col">
            <section class="upper-side flex-row flex-space-between">
                <div class="secondary-info-card">
                    <p class="dark-white-text light-text">STARTED</p>
                    <p><?= $startDateTime ?></p>
                </div>

                <div class="secondary-info-card">
                    <p class="dark-white-text light-text">EXPECTED COMPLETION</p>
                    <p><?= $completionDateTime ?></p>
                </div>

                <div class="secondary-info-card">
                    <p class="dark-white-text light-text">COMPLETED</p>
                    <p><?= $completionDateTime ?></p>
                </div>

                <div class="secondary-info-card">
                    <p class="dark-white-text light-text">BUDGET</p>
                    <p><?= $budget ?></p>
                </div>

                <div class="secondary-info-card">
                    <p class="dark-white-text light-text">CONTINGENCY</p>
                    <p class="orange-text"><?= $contingencyRate ?> APPLIED</p>
                </div>
            </section>

            <?php if ($budgetNote) : ?>
                <div class="budget-note-container">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'about_o.svg' ?>" alt="<?= $budgetNote ?>" title="<?= $budgetNote ?>" height="12">
                        <p class="orange-text"><?= $budgetNote ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
<?php
    return ob_get_clean();
}
