<?php

use App\Dependent\Phase;
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

    $name               = htmlspecialchars($phase->getName());
    $description        = htmlspecialchars($phase->getDescription());
    $startDateTime      = htmlspecialchars(
        dateToWords($phase->getStartDateTime())
    );
    $completionDateTime = htmlspecialchars(
        dateToWords($phase->getCompletionDateTime())
    );
    $status             = $phase->getStatus();

    $statusBadge = WorkStatus::badge($status);

    return <<<HTML
    <hr>

    <!-- Phase Card -->
    <div class="phase-list-card flex-row flex-space-between flex-child-center-h">
        <!-- Phase Info -->
        <div class="flex-col">
            <!-- Phase Name -->
            <h3>$name</h3>

            <div>
                <!-- Project Description -->
                <p class="phase-description wrap-text">$description</p>

                <!-- Project Schedule -->
                <div class="project-schedule flex-row">
                    <div class="text-w-icon">
                        <!-- Start Date -->
                        <img
                            src="{$ICON_PATH}start_w.svg"
                            alt="Phase Schedule"
                            title="Phase Schedule"
                            height="14">

                        <p class="phase-dates">$startDateTime</p>
                    </div>
                    -
                    <div class="text-w-icon">
                        <!-- Completion Date -->
                        <img
                            src="{$ICON_PATH}complete_w.svg"
                            alt="Phase Schedule"
                            title="Phase Schedule"
                            height="14">

                        <p class="phase-dates">$completionDateTime</p>
                    </div>
                    </span>
                </div>
            </div>
        </div>

        <!-- Phase Status -->
        <div class="center-child">
            $statusBadge
        </div>
    </div>
    HTML;
}
