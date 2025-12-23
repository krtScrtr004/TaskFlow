<?php

use App\Core\UUID;
use App\Dependent\Worker;

function selectedWorkerRow(Worker $worker): bool|string
{
    $id = htmlspecialchars(UUID::toString($worker->getPublicId()));
    $name = htmlspecialchars(createFullName($worker->getFirstName(), $worker->getMiddleName(), $worker->getLastName()));

    ob_start();
    ?>

    <!-- Selected workers will be added here dynamically -->
    <tr class="selected-worker-row" data-workerid="<?= $id ?>">
        <td>
            <p class="name multi-line-ellipsis-2"><?= $name ?></p>
        </td>

        <td>
            <div class="input-w-prefix">
                <span class="input-prefix">₱</span>
                <input type="number" class="default-rate-input" id="default_rate" name="default_rate" value="500.00" min="0"
                    max="<?= BUDGET_MAX ?>" step="0.01" required>
            </div>
        </td>

        <td>
            <span class="center-child">
                <button class="unset-button" type="button">
                    <img src="<?= ICON_PATH . 'delete_r.svg' ?>" alt="Remove Worker" title="Remove Worker" height="24">
                </button>
            </span>
        </td>
    </tr>

    <?php
    return ob_get_clean();
}