<?php

use App\Core\UUID;
use App\Dependent\TaskWorker;

/**
 * Generates an HTML card component for a selected task worker in the create task form.
 *
 * This function creates a styled card displaying worker information including their full name,
 * unit rate, and estimated hours assigned. The card includes input fields for modifying
 * the unit rate and hours assigned, as well as a button to remove the worker from the selection.
 *
 * Behavior and side effects:
 * - Sanitizes the worker's public ID and full name using htmlspecialchars() to prevent XSS attacks.
 * - Constructs the full name by combining first, middle, and last names via createFullName().
 * - Uses output buffering (ob_start/ob_get_clean) to capture and return the HTML as a string.
 * - The generated card contains a data-workerid attribute for JavaScript interaction.
 * - Input fields for unit rate and hours assigned are marked as required.
 * - The full name input field is disabled (display only).
 * - References icon assets from the ICON_PATH constant.
 *
 * @param TaskWorker $worker The TaskWorker instance containing worker details to display
 *
 * @return bool|string Returns the generated HTML string on success, or false if output buffering fails
 */
function selectedTaskWorkerCard(TaskWorker $worker): bool|string
{
    $id = htmlspecialchars(UUID::toString($worker->getPublicId()));
    $fullName = htmlspecialchars(
        createFullName(
            $worker->getFirstName(),
            $worker->getMiddleName(),
            $worker->getLastName()
        )
    );
    $unitRate = $worker->getUnitRate() !== DEFAULT_RATE_MIN 
        ? $worker->getUnitRate() 
        : $worker->getDefaultRate();
    $estimatedHoursAssigned = $worker->getEstimatedHour();

    ob_start()
?>
    <div class="selected-task-worker-form-card light-black-bg flex-col" data-workerid="<?= $id ?>">
        <!-- Full Name -->
        <div class="input-label-container">
            <label for="">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Full Name" title="Full Name" height="16">
                    <p class="">Full Name</p>
                </div>
            </label>

            <input type="text" id="" name="" placeholder="Full Name" value="<?= $fullName ?>" disabled>
        </div>

        <div class="multiple-input-row">
            <!-- Unit Rate -->
            <div class="input-label-container">
                <label for="">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'rate_w.svg' ?>" alt="Unit Rate" title="Unit Rate" height="16">
                        <p class="">Unit Rate</p>
                    </div>
                </label>

                <input class="unit-rate-input" type="number" id="" name="" step="0.01" value="<?= $unitRate ?>" placeholder="Unit Rate" required>
            </div>

            <!-- Estimated Hours Assigned -->
            <div class="estimated-hours input-label-container">
                <label for="">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'clock_w.svg' ?>" alt="Hours Assigned" title="Hours Assigned" height="16">
                        <p class="">Hours Assigned</p>
                    </div>
                </label>

                <input class="hours-assigned-input" type="number" id="" name="" step="0.01" value="<?= $estimatedHoursAssigned ?>" placeholder="Hours Assigned" required>
            </div>
        </div>

        <button class="remove-worker-button unset-button">
            <img src="<?= ICON_PATH . 'delete_r.svg' ?>" alt="Remove Worker" title="Remove Worker" height="18">
        </button>

    </div>
<?php
    return ob_get_clean();
}
