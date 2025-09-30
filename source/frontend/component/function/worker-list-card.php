<?php

function workerListCard(Worker $worker): string
{
    $profileLink =
        htmlspecialchars($worker->getProfileLink()) ?:
        ICON_PATH . 'profile_b.svg';
    $name = htmlspecialchars($worker->getFirstName() . ' ' . $worker->getLastName());
    $id = htmlspecialchars($worker->getId());

    return <<<HTML
    <!-- Worker List Card -->
    <button class="worker-list-card unset-button">
        <img
            src="$profileLink"
            alt="$name"
            title="$name"
            height="40">

        <div>
            <h4 class="wrap-text">$name</h4>
            <p><em>$id</em></p>
        </div>
    </button>
    HTML;
}
