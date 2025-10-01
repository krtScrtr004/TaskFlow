<?php

function phaseListCard(Phase $phase): string
{
    $ICON_PATH = ICON_PATH;

    $name = htmlspecialchars($phase->getName());
    $description = htmlspecialchars($phase->getDescription());
    $startDateTime = htmlspecialchars(
        simplifyDate($phase->getStartDateTime())
    );
    $completionDateTime = htmlspecialchars(
        simplifyDate($phase->getCompletionDateTime())
    );
    $status = $phase->getStatus();

    $statusBadge = $status->badge();

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
                            src="{$ICON_PATH}start_b.svg"
                            alt="Phase Schedule"
                            title="Phase Schedule"
                            height="14">

                        <p class="phase-dates">$startDateTime</p>
                    </div>
                    -
                    <div class="text-w-icon">
                        <!-- Completion Date -->
                        <img
                            src="{$ICON_PATH}complete_b.svg"
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
