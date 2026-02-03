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
} from '../../../Utility/WorkRulesValidators.js'
import { Notification } from '../../../Render/Notification.js'
import { getValidationConstraints, die, toggleElementClass } from '../../../Utility/Utility.js'

const errorsMap = {} // Use to track errors

const VALIDATION_CONSTANTS = await getValidationConstraints()
if (!VALIDATION_CONSTANTS)
    console.warn('Failed to load validation constants. Form validation will not work.')

const projectForm = document.querySelector('#project_form')
if (!projectForm) die('Project form not found on the page')

/**
 * Helper to validate an input and update submit button state
 */
function validateAndUpdate(
    input,
    validationResults,
    ruleMapping,
    postValidation = null,
    customId = null
) {
    const rulesContainer = input.closest('.input-rules-container').querySelector('.rules ul')
    const hasInvalid = applyValidationToRules(rulesContainer, validationResults, ruleMapping)
    updateSubmitProjectButtonState(`${input.id || input.name}${customId || ''}`, hasInvalid)
    postValidation?.()
}

/**
 * Project Info
 */
const infoSection = projectForm.querySelector('#info_section')
if (!infoSection) console.warn('Info section not found in the form')

const projectNameInput = infoSection?.querySelector('input[name="name"]')
const projectDescriptionInput = infoSection?.querySelector('textarea[name="description"]')
const projectBudgetInput = infoSection?.querySelector('input[name="budget"]')
const projectMaxWorkersInput = infoSection?.querySelector('input[name="max_workers"]')
const projectStartDateTimeInput = infoSection?.querySelector('input[name="start_date_time"]')
const projectCompletionDateTimeInput = infoSection?.querySelector('input[name="completion_date_time"]')
if (!projectNameInput || !projectDescriptionInput || !projectBudgetInput
    || !projectMaxWorkersInput || !projectStartDateTimeInput || !projectCompletionDateTimeInput)
    console.warn('One or more project info inputs not found in the form')

const projectSchedule = { 'start': projectStartDateTimeInput?.value || new Date(), 'completion': projectCompletionDateTimeInput?.value || new Date() }

projectNameInput?.addEventListener('input', () => {
    validateAndUpdate(
        projectNameInput,
        validateName(projectNameInput.value),
        RULE_MAPPINGS.workName
    )
})

projectDescriptionInput?.addEventListener('input', () => {
    validateAndUpdate(
        projectDescriptionInput,
        validateDescription(projectDescriptionInput.value),
        RULE_MAPPINGS.workDescription
    )
})

projectBudgetInput?.addEventListener('change', () => {
    validateAndUpdate(
        projectBudgetInput,
        validateBudget(projectBudgetInput.value),
        RULE_MAPPINGS.workBudget,
        () => triggerBudgetEvents(projectBudgetInput)
    )
})

projectMaxWorkersInput?.addEventListener('input', () => {
    validateAndUpdate(
        projectMaxWorkersInput,
        validateWorkerCount(projectMaxWorkersInput.value),
        RULE_MAPPINGS.workerCount
    )
})

projectStartDateTimeInput?.addEventListener('input', () => {
    validateAndUpdate(
        projectStartDateTimeInput,
        validateStartDateTime(projectStartDateTimeInput.value),
        RULE_MAPPINGS.startDateTime,
        () => {
            projectSchedule.start = projectStartDateTimeInput.value
            triggerScheduleEvents(projectStartDateTimeInput)
        }
    )
})

projectCompletionDateTimeInput?.addEventListener('input', () => {
    validateAndUpdate(
        projectCompletionDateTimeInput,
        validateCompletionDateTime(projectCompletionDateTimeInput.value, projectStartDateTimeInput.value),
        RULE_MAPPINGS.completionDateTime,
        () => {
            projectSchedule.completion = projectCompletionDateTimeInput.value
            triggerScheduleEvents(projectCompletionDateTimeInput)
        }
    )
})

/**
 * END
 * 
 * Phases Info
 */

const phaseSection = projectForm.querySelector('#phase_section')
if (!phaseSection) console.warn('Phases section not found in the form')
const phaseSchedules = new Map()

phaseSection?.addEventListener('input', e => {
    const card = e.target.closest('.phase-form-card')
    if (!card) return

    const id = card.dataset.phaseid
    if (!id) return

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

    const target = e.target

    // Name validation
    if (target === phaseNameInput) {
        validateAndUpdate(
            phaseNameInput,
            validateName(phaseNameInput.value),
            RULE_MAPPINGS.workName
        )
    }

    // Description validation
    else if (target === phaseDescriptionInput) {
        validateAndUpdate(
            phaseDescriptionInput,
            validateDescription(phaseDescriptionInput.value),
            RULE_MAPPINGS.workDescription,
            null,
            id
        )
    }

    // Contingency rate validation
    else if (target === phaseContingencyRateInput) {
        validateAndUpdate(
            phaseContingencyRateInput,
            validateContingencyRate(phaseContingencyRateInput.value),
            RULE_MAPPINGS.contingencyRate,
            null,
            id
        )
    }

    // Budget note validation
    else if (target === phaseBudgetNoteInput) {
        validateAndUpdate(
            phaseBudgetNoteInput,
            validateBudgetNote(phaseBudgetNoteInput.value),
            RULE_MAPPINGS.budgetNote,
            null,
            id
        )
    }

    // Start date validation
    else if (target === phaseStartDateTimeInput) {
        const val = phaseStartDateTimeInput.value
        const results = validateStartDateTime(val, {
            'isBounded': true,
            'boundStart': projectSchedule.start,
            'boundCompletion': projectSchedule.completion,
            'hasConflict': true,
            'ownId': id,
            'phasesSchedule': phaseSchedules
        })

        validateAndUpdate(
            phaseStartDateTimeInput,
            results,
            RULE_MAPPINGS.startDateTime,
            () => {
                // Update phase schedules map
                Object.values(results).includes(false)
                    ? phaseSchedules.delete(id)
                    : phaseSchedules.set(id, { 'start': val, 'completion': phaseCompletionDateTimeInput.value })
                triggerScheduleEvents(phaseStartDateTimeInput)
            },
            id
        )
    }

    // Completion date validation
    else if (target === phaseCompletionDateTimeInput) {
        const val = phaseCompletionDateTimeInput.value
        const results = validateCompletionDateTime(val, phaseStartDateTimeInput.value, {
            'isBounded': true,
            'boundStart': projectSchedule.start,
            'boundCompletion': projectSchedule.completion,
            'hasConflict': true,
            'ownId': id,
            'phasesSchedule': phaseSchedules
        })

        validateAndUpdate(
            phaseCompletionDateTimeInput,
            results,
            RULE_MAPPINGS.completionDateTime,
            () => {
                // Update phase schedules map
                Object.values(results).includes(false)
                    ? phaseSchedules.delete(id)
                    : phaseSchedules.set(id, { 'start': phaseStartDateTimeInput.value, 'completion': val })                
                triggerScheduleEvents(phaseCompletionDateTimeInput)
            },
            id
        )
    }
})

// Budget validation - separate 'change' event
phaseSection?.addEventListener('change', e => {
    const card = e.target.closest('.phase-form-card')
    if (!card) return

    const id = card.dataset.phaseid || null

    const phaseBudgetInput = card.querySelector('input[name="budget"]')
    const target = e.target

    if (target === phaseBudgetInput) {
        validateAndUpdate(
            phaseBudgetInput,
            validateBudget(phaseBudgetInput.value, parseFloat(projectBudgetInput.value) || 0),
            RULE_MAPPINGS.workBudget,
            () => triggerBudgetEvents(phaseBudgetInput),
            id
        )
    }
})

// Initialize phase schedules
const phaseCards = phaseSection?.querySelectorAll('.phase-form-card') || []
phaseCards.forEach(card => {
    const id = card.dataset.phaseid || null
    const phaseStartDateTimeInput = card.querySelector('input[name="start_date_time"]')
    const phaseCompletionDateTimeInput = card.querySelector('input[name="completion_date_time"]')
    if (!phaseStartDateTimeInput || !phaseCompletionDateTimeInput) return

    const startVal = phaseStartDateTimeInput.value
    const completionVal = phaseCompletionDateTimeInput.value
    phaseSchedules.set(id, { 'start': startVal, 'completion': completionVal })
})

/**
 * END
 * 
 * Workers Info
 */

const workersSection = projectForm.querySelector('#workers_section')
if (!workersSection) console.warn('Workers section not found in the form')

const selectedWorkersTableList = workersSection?.querySelector('.selected-workers-table tbody')
if (!selectedWorkersTableList) console.warn('Selected workers table list not found in the form')

selectedWorkersTableList?.addEventListener('change', e => {
    const row = e.target.closest('tr.selected-worker-row')
    if (!row) return

    const id = row.dataset.workerid
    if (!id) return

    const defaultRateInput = row.querySelector('input.default-rate-input')
    const value = defaultRateInput.value

    function invalidateDefaultRate() {
        toggleElementClass(defaultRateInput.parentElement, ['shake', 'invalid'], [])
        defaultRateInput.parentElement.addEventListener('animationend', () => {
            toggleElementClass(defaultRateInput.parentElement, [], ['shake'])
        }, { once: true })
    }

    // Check if default rates of all workers are within the bound of project budget
    let totalWithinProjectBudget = 0
    const allDefaultRateInputs = selectedWorkersTableList.querySelectorAll('input.default-rate-input')
    allDefaultRateInputs.forEach(input => {
        if (input !== defaultRateInput) {
            const val = parseFloat(input.value)
            if (!isNaN(val)) totalWithinProjectBudget += val
        }
    })
    totalWithinProjectBudget += parseFloat(value) || 0
    if (totalWithinProjectBudget > parseFloat(projectBudgetInput.value || 0)) {
        Notification.error('The total cost of all default rates exceeds the project budget.', 5000)
        updateSubmitProjectButtonState(`${defaultRateInput}${id}`, true)
    }

    const isValidNumber = /^[-+]?\d*\.?\d+$/.test(value)
    const withinRange = value >= VALIDATION_CONSTANTS.DEFAULT_RATE_MIN && value <= VALIDATION_CONSTANTS.DEFAULT_RATE_MAX
    const withinProjectBudget = parseFloat(value) <= parseFloat(projectBudgetInput.value || 0)

    // If a valid number, within the range, and within project budget
    if (isValidNumber && withinRange && withinProjectBudget) {
        defaultRateInput.parentElement.classList.remove('invalid')
        updateSubmitProjectButtonState(`${defaultRateInput}${id}`, false)
    } else {
        invalidateDefaultRate()
        updateSubmitProjectButtonState(`${defaultRateInput}${id}`, true)
    }

    triggerBudgetEvents(defaultRateInput)
})

/**
 * END
 * 
 * Disable form submission if there are validation errors
 */

const submitProjectButton = document.querySelector('.submit-project-button')
if (!submitProjectButton) console.warn('Submit Project button not found in the form')

function updateSubmitProjectButtonState(id, hasInvalid) {
    if (!submitProjectButton) return

    errorsMap[id] = hasInvalid

    let hasGlobalInvalid = Object.values(errorsMap).some(entry => entry === true)
    if (hasGlobalInvalid) {
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
 *  Event automatic triggers
 */

const eventTrigger = new InputEvent('change', {
    bubbles: true,
    cancelable: true
})

/**
 * Triggers budget-related events across all budget input fields.
 *
 * This function dispatches a budget change event to the project budget input,
 * all phase budget inputs, and all worker default rate inputs, excluding the
 * target element that initiated the trigger. Uses a flag to prevent recursive
 * event triggering and queues the flag reset asynchronously.
 *
 * @param {HTMLElement} target The input element that triggered the budget event
 *
 * @return {void}
 */
let isBudgetEventTriggering = false
function triggerBudgetEvents(target) {
    if (isBudgetEventTriggering) return
    isBudgetEventTriggering = true

    // Dispatch to project budget input
    if (projectBudgetInput !== target) projectBudgetInput.dispatchEvent(eventTrigger)

    // Dispatch to phases budget input
    const phasesBudgetInput = phaseSection.querySelectorAll('.phase-form-card input[name="budget"]')
    phasesBudgetInput.forEach(card => {
        if (card !== target) card.dispatchEvent(eventTrigger)
    })

    const selectedWorkersDefaultRate = selectedWorkersTableList.querySelectorAll('input.default-rate-input')
    selectedWorkersDefaultRate.forEach(selected => {
        if (selected !== target) selected.dispatchEvent(eventTrigger)
    })

    queueMicrotask(() => isBudgetEventTriggering = false)
}

/**
 * Triggers schedule-related input events across the project form.
 *
 * Prevents recursive/duplicate dispatches by using the global `isScheduleEventTriggering`
 * guard. When invoked, this function dispatches the global `eventTrigger` event to
 * project-level start/completion inputs and to each phase card's start/completion inputs,
 * skipping the originating `target` input. The guard is cleared asynchronously with
 * queueMicrotask to allow subsequent triggers after the current propagation completes.
 *
 * Relies on the following globals being available in the surrounding scope:
 *  - isScheduleEventTriggering: boolean guard to avoid re-entrancy
 *  - projectStartDateTimeInput: input element for project start
 *  - projectCompletionDateTimeInput: input element for project completion
 *  - phaseSection: container element that holds .phase-form-card elements
 *  - eventTrigger: Event object to dispatch
 *
 * @param {EventTarget|null} target The input element that originated the trigger; that element will be skipped.
 * @returns {void}
 */
let isScheduleEventTriggering = false
function triggerScheduleEvents(target) {
    if (isScheduleEventTriggering) return
    isScheduleEventTriggering = true

    // Dispatch to project inputs
    if (projectStartDateTimeInput !== target) projectStartDateTimeInput.dispatchEvent(eventTrigger)
    if (projectCompletionDateTimeInput !== target) projectCompletionDateTimeInput.dispatchEvent(eventTrigger)

    const phasesCards = phaseSection.querySelectorAll('.phase-form-card')
    phasesCards.forEach(card => {
        const startDateTimeInput = card.querySelector('input[name="start_date_time"]')
        if (startDateTimeInput && startDateTimeInput !== target) startDateTimeInput.dispatchEvent(eventTrigger)

        const completionDateTimeInput = card.querySelector('input[name="completion_date_time"]')
        if (completionDateTimeInput && completionDateTimeInput !== target) completionDateTimeInput.dispatchEvent(eventTrigger)
    })

    queueMicrotask(() => isScheduleEventTriggering = false)
}