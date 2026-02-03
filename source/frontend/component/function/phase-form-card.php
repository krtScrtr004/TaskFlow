<?php

use App\Core\UUID;
use App\Entity\Phase;

/**
 * Renders an HTML form card for creating or editing a project phase.
 *
 * When a Phase object is provided, its properties populate the form fields; otherwise the form
 * is rendered with empty/default values. Field values are escaped with htmlspecialchars before
 * output and date values are formatted using formatDateTime(..., 'Y-m-d'). The generated markup
 * includes inputs for name, description, budget, contingency rate, budget note, start date, and
 * completion date, and it injects validation/rules snippets produced by helper functions such as
 * workNameRules(), workDescriptionRules(), workBudgetRules(), workContingencyRateRules(),
 * workBudgetNoteRules(), workStartDateTimeRules(), and workCompletionDateTimeRules().
 *
 * Note: The function uses ICON_PATH and various constants (e.g. NAME_MIN, NAME_MAX, CONTINGENCY_RATE_MIN,
 * CONTINGENCY_RATE_MAX, LONG_TEXT_MIN, LONG_TEXT_MAX) to build attributes and assets.
 *
 * @param Phase|null $phase Phase domain object to populate the form, or null to render a blank form
 *
 * @return string|bool Rendered HTML string on success, or false on failure
 */
function phaseFormCard(?Phase $phase): bool|string
{
    $phaseData = [
        'id'                    => '',
        'name'                  => '',
        'description'           => '',
        'budget'                => '',
        'contingencyRate'       => '',
        'budgetNote'            => '',
        'startDateTime'         => '',
        'completionDateTime'    => '',
    ];
    if ($phase) {
        $phaseData['id']                    = htmlspecialchars(UUID::toString($phase->getPublicId()));
        $phaseData['name']                  = htmlspecialchars($phase->getName());
        $phaseData['description']           = htmlspecialchars($phase->getDescription());
        $phaseData['budget']                = htmlspecialchars($phase->getBudget());
        $phaseData['contingencyRate']       = htmlspecialchars($phase->getContingencyRate());
        $phaseData['budgetNote']            = htmlspecialchars($phase->getBudgetNote());
        $phaseData['startDateTime']         = htmlspecialchars(formatDateTime($phase->getStartDateTime(), 'Y-m-d'));
        $phaseData['completionDateTime']    = htmlspecialchars(formatDateTime($phase->getCompletionDateTime(), 'Y-m-d'));
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
            <div class="input-rules-container">
                <div class="input-label-container">
                    <label for="name">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Name" title="Name" height="24">
                            <p>Name</p>
                        </div>
                    </label>
                    <input type="text" name="name" id="name" placeholder="(eg. Requirement Analysis)" min="<?= NAME_MIN ?>"
                        max="<?= NAME_MAX ?>" value="<?= $phaseData['name'] ?>" autocapitalize="on" autocomplete="on"
                        required>
                </div>

                <?= workNameRules() ?>
            </div>

            <div class="input-rules-container">
                <div class="input-label-container">
                    <label for="description">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Description" title="Description" height="24">
                            <p>Description <span class="minified-text dark-white-text"> (Optional)</span></p>
                        </div>
                    </label>
                    <textarea name="description" id="description" rows="8"
                        placeholder="Describe the phase objectives, scope, and deliverables (eg. Gather and analyze project requirements from stakeholders to define project scope and objectives.)"
                        min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>" autocapitalize="on" autocomplete="on"
                        required><?= $phaseData['description'] ?></textarea>
                </div>

                <?= workDescriptionRules() ?>
            </div>

            <section class="row-inputs flex-row">
                <div class="input-rules-container">
                    <div class="input-label-container">
                        <label for="budget">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'budget_w.svg' ?>" alt="Budget" title="Budget" height="24">
                                <p>Budget</p>
                            </div>
                        </label>
                        <div class="input-w-prefix">
                            <span class="input-prefix">₱</span>
                            <input type="number" name="budget" id="budget" value="<?= $phaseData['budget'] ?>"
                                placeholder="Enter the budget amount for this phase (e.g., 10000)" required>
                        </div>
                    </div>

                    <?= workBudgetRules(1) ?>
                </div>

                <div class="input-rules-container">
                    <div class="input-label-container">
                        <label for="contingency_rate">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'safe_w.svg' ?>" alt="Contingency Rate" title="Contingency Rate"
                                    height="24">
                                <p>Contingency Rate</p>
                            </div>
                        </label>
                        <input type="number" name="contingency_rate" id="contingency_rate"
                            value="<?= $phaseData['contingencyRate'] ?>" placeholder="How much contingency rate to allocate (e.g., 10)" min="<?= CONTINGENCY_RATE_MIN ?>"
                            max="<?= CONTINGENCY_RATE_MAX ?>" required>
                    </div>

                    <?= workContingencyRateRules() ?>
                </div>
            </section>

            <div class="input-rules-container">
                <div class="input-label-container">
                    <label for="budget_note">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Budget Note" title="Budget Note" height="24">
                            <p>Budget Note <span class="minified-text dark-white-text"> (Optional)</span></p>
                        </div>
                    </label>
                    <textarea name="budget_note" id="budget_note" rows="4"
                        placeholder="Provide additional details about the budget allocation for this phase (eg. Include costs for resources, tools, and contingency funds.)"
                        min="<?= LONG_TEXT_MIN ?>" max="<?= LONG_TEXT_MAX ?>" autocapitalize="on" autocomplete="on"
                        required><?= $phaseData['budgetNote'] ?></textarea>
                </div>

                <?= workBudgetNoteRules() ?>
            </div>

            <section class="row-inputs flex-row">

                <div class="input-rules-container">
                    <div class="input-label-container">
                        <label for="start_date_time">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'start_w.svg' ?>" alt="Start Date" title="Start Date" height="24">
                                <p>Start Date</p>
                            </div>
                        </label>
                        <input type="date" name="start_date_time" id="start_date_time"
                            min="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>" value="<?= $phaseData['startDateTime'] ?>"
                            required>
                    </div>

                    <?= workStartDateTimeRules(1) ?>
                </div>

                <div class="input-rules-container">
                    <div class="input-label-container">
                        <label for="completion_date_time">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'complete_w.svg' ?>" alt="Completion Date" title="Completion Date"
                                    height="24">
                                <p>Completion Date</p>
                            </div>
                        </label>
                        <input type="date" name="completion_date_time" id="completion_date_time"
                            min="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>"
                            value="<?= $phaseData['completionDateTime'] ?>" required>
                    </div>

                    <?= workCompletionDateTimeRules(1) ?>
                </div>
            </section>
    </div>
<?php
    return ob_get_clean();
}
