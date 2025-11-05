<?php

use App\Entity\Project;
use App\Core\UUID;
use App\Enumeration\WorkStatus;

function projectGridCard(Project $project): string
{
    $id = htmlspecialchars(UUID::toString($project->getPublicId()));
    $name = htmlspecialchars($project->getName());
    $description = htmlspecialchars($project->getDescription()) ?? 'No description provided';
    $startDateTime = htmlspecialchars(
        dateToWords($project->getStartDateTime())
    );
    $completionDateTime = htmlspecialchars(
        dateToWords($project->getCompletionDateTime())
    );
    $status = $project->getStatus();

    ob_start();
    ?>
    <div class="project-grid-card">
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
            <p class="project-description multi-line-ellipsis-7" title="<?= $description ?>">
                <?= $description ?>
            </p>

            <!-- Status -->
            <div class="project-status flex-col flex-child-end-v">
                <?= WorkStatus::badge($status) ?>
            </div>
        </a>
    </div>
    <?php
    return ob_get_clean();
}