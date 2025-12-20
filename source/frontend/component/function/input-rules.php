<?php

function workNameRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be between <?= NAME_MIN ?> and <?= NAME_MAX ?> characters.</li>
            <li>Must not contain three or more consecutive special characters.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function workDescriptionRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be between <?= LONG_TEXT_MIN ?> and <?= LONG_TEXT_MAX ?> characters.</li>
            <li>Must not contain three or more consecutive special characters.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function workWorkerCountRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a positive integer.</li>
            <li>Maximum number of workers is <?= WORKER_COUNT_MAX ?>.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function workBudgetRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a positive number.</li>
            <li>Maximum budget is ₱<?= number_format(BUDGET_MAX, 2) ?>.</li>
            <li>Have up to two decimal places.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function workContingencyRateRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a number between <?= CONTINGENCY_RATE_MIN ?> and <?= CONTINGENCY_RATE_MAX ?>.</li>
            <!-- <li>Represents the percentage of budget reserved for unexpected costs.</li> -->
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function workBudgetNoteRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be between <?= LONG_TEXT_MIN ?> and <?= LONG_TEXT_MAX ?> characters.</li>
            <li>Must not contain three or more consecutive special characters.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function workStartDateTimeRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a valid date.</li>
            <li>Must be between <?= YEAR_MIN ?> and <?= YEAR_MAX ?>.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function workCompletionDateTimeRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a valid date.</li>
            <li>Must be between <?= YEAR_MIN ?> and <?= YEAR_MAX ?>.</li>
            <li>Must be after the start date.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}