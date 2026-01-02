<?php

use App\Dependent\Worker;

function selectedCreateTaskWorkerCard(Worker $worker): bool|string
{
    $id = $worker->getPublicId();
    $fullName = htmlspecialchars(
        createFullName(
            $worker->getFirstName(),
            $worker->getMiddleName(),
            $worker->getLastName()
        )
    );
    // $unitRate = 
    // $estimatedHoursAssigned = 

    ob_start()
?>
    <div class="selected-task-worker-form black-bg flex-col">
        <!-- Full Name -->
        <div class="input-label-container">
            <label for="">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Full Name" title="Full Name" height="16">
                    <p class="">Full Name</p>
                </div>
            </label>

            <input type="text" id="" name="" placeholder="Full Name" value="" disabled>
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

                <input type="number" id="" name="" step="0.01" value="" placeholder="Unit Rate" required>
            </div>

            <!-- Estimated Hours Assigned -->
            <div class="estimated-hours input-label-container">
                <label for="">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'clock_w.svg' ?>" alt="Estimated Hours Assigned" title="Estimated Hours Assigned" height="16">
                        <p class="">Estimated Hours Assigned</p>
                    </div>
                </label>

                <input type="number" id="" name="" step="0.01" value="" placeholder="Estimated Hours Assigned" required>
            </div>
        </div>

        <button class="remove-worker-button unset-button">
            <img src="<?= ICON_PATH . 'delete_r.svg' ?>" alt="Remove Worker" title="Remove Worker" height="18">
        </button>

    </div>
<?php
    return ob_get_clean();
}
