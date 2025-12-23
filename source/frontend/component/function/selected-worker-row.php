<?php

use App\Core\UUID;
use App\Dependent\Worker;

/**
 * Generates an HTML table row representing a selected worker.
 *
 * Builds a sanitized, ready-to-insert <tr> fragment containing the worker's
 * display name and controls for the worker's default rate and removal.
 * The worker's public UUID is converted to string and escaped for use in the
 * data-workerid attribute, and the full name is constructed via
 * createFullName(...) and escaped for safe output.
 *
 * The returned row includes:
 *  - a <p> element with class "name multi-line-ellipsis-2" containing the worker name,
 *  - an input of type="number" with class "default-rate-input" and id/name "default_rate"
 *    prefilled to 500.00, min="0", max set to the BUDGET_MAX constant, step="0.01", and required,
 *  - a remove button rendering an image from ICON_PATH . 'delete_r.svg'.
 *
 * Note: All dynamic values are passed through htmlspecialchars() to prevent XSS.
 *
 * @param Worker $worker Worker entity used to populate the row (public ID and name)
 *
 * @return string|bool HTML string of the table row on success, or false on failure
 */
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
                <button class="remove-worker-button unset-button" type="button">
                    <img src="<?= ICON_PATH . 'delete_r.svg' ?>" alt="Remove Worker" title="Remove Worker" height="24">
                </button>
            </span>
        </td>
    </tr>

    <?php
    return ob_get_clean();
}