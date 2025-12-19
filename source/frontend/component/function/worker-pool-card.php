<?php

use App\Dependent\Worker;

function workerPoolCard(Worker $worker): bool|string
{
    // TODO: Implement dynamic worker card rendering
    ob_start();
    ?>
    <li>
        <button class="worker-pool-card unset-button" type="button">
            <img src="<?= ICON_PATH . 'profile_w.svg' ?>" class="circle fit-cover" alt="" height="55">

            <div class="flex-col flex-child-start-h worker-info">
                <span class="name">John Doe</span>
                <div class="flex-row flex-wrap">
                    <span class="role-chip chip badge light-text">Developer</span>
                </div>
            </div>
        </button>
    </li>
    <?php
    return ob_get_clean();

}