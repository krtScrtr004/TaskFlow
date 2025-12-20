<?php

use App\Core\UUID;
use App\Dependent\Phase;

function phaseFormCard(?Phase $phase): bool|string
{
    $phaseData = [
        'id'                    => '',
        'name'                  => '',
        'description'           => '',
        'budget'                => '',
        'contingencyRate'       => '',
        'budgetNote'            => '',
        'startDateTime'         => formatDateTime(new DateTime(), 'Y-m-d'),
        'completionDateTime'    => formatDateTime(new DateTime(), 'Y-m-d'),
    ];
    if ($phase) {
        $phaseData['id'] = htmlspecialchars(UUID::toString($phase->getPublicId()));
        $phaseData['name'] = htmlspecialchars($phase->getName());
        $phaseData['description'] = htmlspecialchars($phase->getDescription());
        // TODO: Implement these changes
        // $phaseData['budget'] = htmlspecialchars($phase->getBudget());
        // $phaseData['contingencyRate'] = htmlspecialchars($phase->getContingencyRate());
        // $phaseData['budgetNote'] = htmlspecialchars($phase->getBudgetNote());
        $phaseData['startDateTime'] = htmlspecialchars(formatDateTime($phase->getStartDateTime(), 'Y-m-d'));
        $phaseData['completionDateTime'] = htmlspecialchars(formatDateTime($phase->getCompletionDateTime(), 'Y-m-d'));
    }
    
    ob_start();
    ?>
    <div class="phase-form-card flex-col fade-in" data-phaseid="<?= $phaseData['id'] ?>">
        <section class="heading flex-row flex-space-between black-bg">
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'phase_w.svg' ?>" alt="Phase 1" title="Phase 1" height="24">
                <h2>New Project Phase</h2>
            </div>

            <button class="remove-phase-button unset-button" type="button">
                <img src="<?= ICON_PATH . 'delete_r.svg' ?>" alt="" height="24">
            </button>
        </section>

        <section class="inputs-section flex-col">
            <div class="input-label-container">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Name" title="Name" height="24">
                    <label for="name">Name</label>
                </div>
                <input type="text" name="name" id="name" placeholder="(eg. Requirement Analysis)" min="<?= NAME_MIN ?>"
                    max="<?= NAME_MAX ?>" value="<?= $phaseData['name'] ?>" autocapitalize="on" autocomplete="on" required>
            </div>

            <div class="input-label-container">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Description" title="Description" height="24">
                    <label for="description">Description</label>
                </div>
                <textarea name="description" id="description" rows="4"
                    placeholder="Describe the phase objectives, scope, and deliverables (optional)"
                    min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>" autocapitalize="on" autocomplete="on"
                    required><?= $phaseData['description'] ?></textarea>
            </div>

            <section class="row-inputs flex-row">
                <div class="input-label-container">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'budget_w.svg' ?>" alt="Budget" title="Budget" height="24">
                        <label for="budget">Budget</label>
                    </div>
                    <div class="input-w-prefix">
                        <span class="input-prefix">₱</span>
                        <input type="number" name="budget" id="budget" value="<?= $phaseData['budget'] ?>" placeholder="0.00" required>
                    </div>
                </div>

                <div class="input-label-container">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'safe_w.svg' ?>" alt="Contingency Rate" title="Contingency Rate"
                            height="24">
                        <label for="contingency_rate">Contingency Rate</label>
                    </div>
                    <input type="number" name="contingency_rate" id="contingency_rate" value="<?= $phaseData['contingencyRate'] ?>" placeholder="0"
                        min="<?= CONTINGENCY_RATE_MIN ?>" max="<?= CONTINGENCY_RATE_MAX ?>" required>
                </div>
            </section>

            <div class="input-label-container">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Budget Note" title="Budget Note" height="24">
                    <label for="budget_note">Budget Note</label>
                </div>
                <textarea name="budget_note" id="budget_note" rows="4"
                    placeholder="Provide additional details about the budget allocation for this phase (optional)"
                    min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>" autocapitalize="on" autocomplete="on"
                    required><?= $phaseData['budgetNote'] ?></textarea>
            </div>

            <section class="row-inputs flex-row">
                <div class="input-label-container">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date" height="24">
                        <label for="start_date_time">Start Date</label>
                    </div>
                    <input type="date" name="start_date_time" id="start_date_time"
                        value="<?= $phaseData['startDateTime'] ?>" required>
                </div>

                <div class="input-label-container">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completion Date" title="Completion Date"
                            height="24">
                        <label for="completion_date_time">End Date</label>
                    </div>
                    <input type="date" name="completion_date_time" id="completion_date_time"
                        value="<?= $phaseData['completionDateTime'] ?>" required>
                </div>
            </section>
    </div>
    <?php
    return ob_get_clean();
}