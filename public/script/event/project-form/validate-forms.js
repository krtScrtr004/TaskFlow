import {
    validateName,
    applyValidationToRules,
    RULE_MAPPINGS,
    validateDescription,
    validateBudget,
    validateWorkerCount,
    validateStartDateTime,
    validateCompletionDateTime,
    validateBudgetNote,
    validateContingencyRate
} from '../../utility/work-rules-validators.js'
import { Notification } from '../../render/notification.js'
import { getValidationConstraints, die } from '../../utility/utility.js'

const VALIDATION_CONSTANTS = await getValidationConstraints()
if (!VALIDATION_CONSTANTS) {
    console.warn('Failed to load validation constants. Form validation will not work.')
}

const projectForm = document.querySelector('#project_form')
if (!projectForm) {
    die('Project form not found on the page')
}

/**
 * Project Info
 */
const infoSection = projectForm.querySelector('#info_section')
if (!infoSection) {
    console.warn('Info section not found in the form')
}

const projectNameInput = infoSection?.querySelector('input[name="name"]')
const projectDescriptionInput = infoSection?.querySelector('textarea[name="description"]')
const projectBudgetInput = infoSection?.querySelector('input[name="budget"]')
const projectMaxWorkersInput = infoSection?.querySelector('input[name="max_workers"]')
const projectStartDateTimeInput = infoSection?.querySelector('input[name="start_date_time"]')
const projectCompletionDateTimeInput = infoSection?.querySelector('input[name="completion_date_time"]')
if (!projectNameInput || !projectDescriptionInput || !projectBudgetInput
    || !projectMaxWorkersInput || !projectStartDateTimeInput || !projectCompletionDateTimeInput) {
    console.warn('One or more project info inputs not found in the form')
}
const projectSchedule = { 'start': projectStartDateTimeInput?.value || new Date(), 'completion': projectCompletionDateTimeInput?.value || new Date() }

projectNameInput?.addEventListener('input', () => {
    const results = validateName(projectNameInput.value)
    const rulesContainer = projectNameInput.closest('.input-rules-container').querySelector('.rules ul')
    updateSubmitProjectButtonState(
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workName)
    )
})

projectDescriptionInput?.addEventListener('input', () => {
    const results = validateDescription(projectDescriptionInput.value)
    const rulesContainer = projectDescriptionInput.closest('.input-rules-container').querySelector('.rules ul')
    updateSubmitProjectButtonState(
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workDescription)
    )
})

projectBudgetInput?.addEventListener('input', () => {
    const results = validateBudget(projectBudgetInput.value)
    const rulesContainer = projectBudgetInput.closest('.input-rules-container').querySelector('.rules ul')
    updateSubmitProjectButtonState(
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workBudget)
    )
})

projectMaxWorkersInput?.addEventListener('input', () => {
    const results = validateWorkerCount(projectMaxWorkersInput.value)
    const rulesContainer = projectMaxWorkersInput.closest('.input-rules-container').querySelector('.rules ul')
    updateSubmitProjectButtonState(
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workerCount)
    )
})

projectStartDateTimeInput?.addEventListener('input', () => {
    const results = validateStartDateTime(projectStartDateTimeInput.value)
    const rulesContainer = projectStartDateTimeInput.closest('.input-rules-container').querySelector('.rules ul')
    updateSubmitProjectButtonState(
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.startDateTime)
    )
    projectSchedule.start = projectStartDateTimeInput.value
})

projectCompletionDateTimeInput?.addEventListener('input', () => {
    const results = validateCompletionDateTime(projectCompletionDateTimeInput.value, projectStartDateTimeInput.value)
    const rulesContainer = projectCompletionDateTimeInput.closest('.input-rules-container').querySelector('.rules ul')
    updateSubmitProjectButtonState(
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.completionDateTime)
    )
    projectSchedule.completion = projectCompletionDateTimeInput.value
})

/**
 * END
 * 
 * Phases Info
 */

const phaseSection = projectForm.querySelector('#phase_section')
if (!phaseSection) {
    console.warn('Phases section not found in the form')
}
const phaseSchedules = new Map()

phaseSection?.addEventListener('input', e => {
    const card = e.target.closest('.phase-form-card')
    if (!card) {
        return
    }
    const id = card.dataset.phaseid || null

    const phaseNameInput = card.querySelector('input[name="name"]')
    const phaseDescriptionInput = card.querySelector('textarea[name="description"]')
    const phaseBudgetInput = card.querySelector('input[name="budget"]')
    const phaseContingencyRateInput = card.querySelector('input[name="contingency_rate"]')
    const phaseBudgetNoteInput = card.querySelector('textarea[name="budget_note"]')
    const phaseStartDateTimeInput = card.querySelector('input[name="start_date_time"]')
    const phaseCompletionDateTimeInput = card.querySelector('input[name="completion_date_time"]')
    if (!phaseNameInput || !phaseDescriptionInput || !phaseBudgetInput
        || !phaseContingencyRateInput || !phaseStartDateTimeInput || !phaseCompletionDateTimeInput) {
        console.warn('One or more phase inputs not found in the form card')
        return
    }

    if (e.target === phaseNameInput) {
        const results = validateName(phaseNameInput.value)
        const rulesContainer = phaseNameInput.closest('.input-rules-container').querySelector('.rules ul')
        updateSubmitProjectButtonState(
            applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workName)
        )
    }

    if (e.target === phaseDescriptionInput) {
        const results = validateDescription(phaseDescriptionInput.value)
        const rulesContainer = phaseDescriptionInput.closest('.input-rules-container').querySelector('.rules ul')
        updateSubmitProjectButtonState(
            applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workDescription)
        )
    }

    if (e.target === phaseBudgetInput) {
        const results = validateBudget(phaseBudgetInput.value, parseFloat(projectBudgetInput.value) || 0)
        const rulesContainer = phaseBudgetInput.closest('.input-rules-container').querySelector('.rules ul')
        updateSubmitProjectButtonState(
            applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workBudget)
        )
    }

    if (e.target === phaseContingencyRateInput) {
        const results = validateContingencyRate(phaseContingencyRateInput.value)
        const rulesContainer = phaseContingencyRateInput.closest('.input-rules-container').querySelector('.rules ul')
        updateSubmitProjectButtonState(
            applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.contingencyRate)
        )
    }

    if (e.target === phaseBudgetNoteInput) {
        const results = validateBudgetNote(phaseBudgetNoteInput.value)
        const rulesContainer = phaseBudgetNoteInput.closest('.input-rules-container').querySelector('.rules ul')
        updateSubmitProjectButtonState(
            applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.budgetNote)
        )
    }

    if (e.target === phaseStartDateTimeInput) {
        const val = phaseStartDateTimeInput.value

        const results = validateStartDateTime(val, {
            'isBounded': true,
            'boundStart': projectSchedule.start,
            'boundCompletion': projectSchedule.completion,

            'hasConflict': true,
            'ownId': id,
            'phasesSchedule': phaseSchedules
        })
        const rulesContainer = phaseStartDateTimeInput.closest('.input-rules-container').querySelector('.rules ul')
        updateSubmitProjectButtonState(
            applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.startDateTime)
        )
        if (Object.values(results).includes(false)) {
            phaseSchedules.delete(id)
        } else {
            phaseSchedules.set(id, { 'start': val, 'completion': phaseCompletionDateTimeInput.value })
        }
    }

    if (e.target === phaseCompletionDateTimeInput) {
        const val = phaseCompletionDateTimeInput.value

        const results = validateCompletionDateTime(val, phaseStartDateTimeInput.value, {
            'isBounded': true,
            'boundStart': projectSchedule.start,
            'boundCompletion': projectSchedule.completion,

            'hasConflict': true,
            'ownId': id,
            'phasesSchedule': phaseSchedules
        })
        const rulesContainer = phaseCompletionDateTimeInput.closest('.input-rules-container').querySelector('.rules ul')
        updateSubmitProjectButtonState(
            applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.completionDateTime)
        )
        if (Object.values(results).includes(false)) {
            phaseSchedules.delete(id)
        } else {
            phaseSchedules.set(id, { 'start': phaseStartDateTimeInput.value, 'completion': val })
        }
    }
})

/**
 * END
 * 
 * Workers Info
 */

const workersSection = projectForm.querySelector('#workers_section')
if (!workersSection) {
    console.warn('Workers section not found in the form')
}

const selectedWorkersTableList = workersSection?.querySelector('.selected-workers-table tbody')
if (!selectedWorkersTableList) {
    console.warn('Selected workers table list not found in the form')
}

selectedWorkersTableList?.addEventListener('input', e => {
    const row = e.target.closest('tr.selected-worker-row')
    if (!row) {
        return
    }

    const defaultRateInput = row.querySelector('input.default-rate-input')
    const value = defaultRateInput.value

    function invalidateDefaultRate() {
        defaultRateInput.parentElement.classList.add('shake', 'invalid')
        defaultRateInput.parentElement.addEventListener('animationend', () => {
            defaultRateInput.parentElement.classList.remove('shake')
        })
    }

    let totalWithinProjectBudget = 0
    const allDefaultRateInputs = selectedWorkersTableList.querySelectorAll('input.default-rate-input')
    allDefaultRateInputs.forEach(input => {
        if (input !== defaultRateInput) {
            const val = parseFloat(input.value)
            if (!isNaN(val)) {
                totalWithinProjectBudget += val
            }
        }
    })
    totalWithinProjectBudget += parseFloat(value) || 0
    if (totalWithinProjectBudget > parseFloat(projectBudgetInput.value || 0)) {
        Notification.error('The total of all default rates exceeds the project budget.', 5000)
        updateSubmitProjectButtonState(true)
    }

    const isValidNumber = /^[-+]?\d*\.?\d+$/.test(value)
    const withinRange = value >= VALIDATION_CONSTANTS.DEFAULT_RATE_MIN && value <= VALIDATION_CONSTANTS.DEFAULT_RATE_MAX
    const withinProjectBudget = parseFloat(value) <= parseFloat(projectBudgetInput.value || 0)

    // If a valid number, within the range, and within project budget
    if (isValidNumber && withinRange && withinProjectBudget) {
        defaultRateInput.parentElement.classList.remove('invalid')
        updateSubmitProjectButtonState(false)
    } else {
        invalidateDefaultRate()
        updateSubmitProjectButtonState(true)
    }
})

/**
 * END
 * 
 * Disable form submission if there are validation errors
 */

const submitProjectButton = document.querySelector('.submit-project-button')
if (!submitProjectButton) {
    console.warn('Submit Project button not found in the form')
}
function updateSubmitProjectButtonState(hasInvalid) {
    if (!submitProjectButton) {
        return
    }

    if (hasInvalid) {
        submitProjectButton.disabled = true
        submitProjectButton.title = 'Please fix validation errors before submitting the form.'
    } else {
        submitProjectButton.disabled = false
        submitProjectButton.title = ''
    }
}

/**
 * END
 * 
 * Initialize phase schedules
 */

const phaseCards = phaseSection?.querySelectorAll('.phase-form-card') || []
phaseCards.forEach(card => {
    const id = card.dataset.phaseid || null
    const phaseStartDateTimeInput = card.querySelector('input[name="start_date_time"]')
    const phaseCompletionDateTimeInput = card.querySelector('input[name="completion_date_time"]')
    if (!phaseStartDateTimeInput || !phaseCompletionDateTimeInput) {
        return
    }
    const startVal = phaseStartDateTimeInput.value
    const completionVal = phaseCompletionDateTimeInput.value
    phaseSchedules.set(id, { 'start': startVal, 'completion': completionVal })
})
