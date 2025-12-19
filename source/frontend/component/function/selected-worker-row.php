<?php

use App\Entity\User;

function selectedWorkerRow(User $user): bool|string
{
    // TODO: Implement dynamic selected worker row rendering
    ob_start();
    ?>

    <!-- Selected workers will be added here dynamically -->
    <tr class="selected-worker-row">
        <td>
            <p class="name multi-line-ellipsis-2">John DoeJohn DoeJohn Doe</p>
        </td>

        <td>
            <div class="roles flex-row flex-wrap">
                <span class="role-chip badge">Developer</span>
            </div>
        </td>

        <td>
            <div class="input-w-prefix">
                <span class="input-prefix">₱</span>
                <input type="number" class="default-rate-input" value="500.00" min="0" max="<?= BUDGET_MAX ?>" step="0.01"
                    required>
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