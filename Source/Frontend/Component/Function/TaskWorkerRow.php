<?php

use App\Entity\TaskWorker;
use App\Enumeration\WorkerStatus;

function taskWorkerRow(TaskWorker $worker): bool|string
{
    $profileLink = $worker->getProfileLink() ?? ICON_PATH . 'profile_w.svg';
    $name = createFullName($worker->getFirstName(), $worker->getMiddleName(), $worker->getLastName());
    $email = $worker->getEmail();
    $contactNumber = $worker->getContactNumber();
    $status = $worker->getStatus();

    ob_start();
?>
    <tr class=" task-worker-row light-black-bg">
        <!-- Profile -->
        <td>
            <div class="center-child">
                <img src="<?= $profileLink ?>" alt="<?= $name ?>" title="<?= $name ?>" height="32">
            </div>
        </td>

        <!-- Name & Contact -->
        <td>
            <div class="name-n-contact">
                <p class="worker-name"><?= $name ?></p>

                <div class="contact flex-col">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'email_dw.svg' ?>" alt="Email" title="Email" height="14">
                        <p class="worker-email dark-white-text"><?= $email ?></p>
                    </div>
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'contact_dw.svg' ?>" alt="Contact Number" title="Contact Number" height="14">
                        <p class="worker-contact dark-white-text"><?= $contactNumber ?></p>
                    </div>
                </div>
            </div>
        </td>

        <!-- Subtasks Completed -->
        <td>
            <!-- TODO -->
        </td>

        <!-- Status -->
        <td>
            <div class="center-child">
                <?= WorkerStatus::badge($status) ?>
            </div>
        </td>

        <!-- Actions -->
        <td>

        </td>
    </tr>
<?php
    return ob_get_clean();
}
