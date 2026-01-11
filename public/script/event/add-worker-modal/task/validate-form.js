/**
 * TODO: Check task estimated cost against phase budget
 * TODO: Propagate cost validation to workers' unit rate
 * TODO: Check task schedule against phase schedule
 */

import {
    validateName,
    validateDescription,
    validateBudget,
    validateBudgetNote,
    validateStartDateTime,
    validateCompletionDateTime,
    applyValidationToRules,
    RULE_MAPPINGS
} from '../../../utility/work-rules-validators.js'
import { getValidationConstraints, toggleElementClass } from '../../../utility/utility.js'
import { Notification } from '../../../render/notification.js'

const errorsMap = {}

const VALIDATION_CONSTANTS = await getValidationConstraints()
if (!VALIDATION_CONSTANTS) console.warn('Validation constants not found')

/**
 * Helper to validate an input and update submit button state
 */
function validateAndUpdate(
    input,
    validationResults,
    ruleMapping,
    postValidation = null,
) {
    const rulesContainer = input.closest('.input-rules-container').querySelector('.rules ul')
    const hasInvalid = applyValidationToRules(rulesContainer, validationResults, ruleMapping)
    updateSubmitTaskButtonState(`${input.id}`, hasInvalid)
    postValidation?.()
}

const taskForm = document.querySelector('#task_form')
if (!taskForm) console.warn('Task form element not found')

const hiddenData = taskForm.querySelector('.hidden-data.no-display')
const phaseBudget = hiddenData
    ? parseFloat(hiddenData.dataset.phasebudget)
    : null
const phaseStartDateTime = hiddenData
    ? hiddenData.dataset.phasestartdatetime
    : null
const phaseCompletionDateTime = hiddenData
    ? hiddenData.dataset.phasecompletiondatetime
    : null

const boundObject = phaseStartDateTime && phaseCompletionDateTime
    ? {
        'isBounded': true,
        'boundStart': phaseStartDateTime,
        'boundCompletion': phaseCompletionDateTime
    } : {}

/**
 *  Task Info
 */
const taskInfo = taskForm.querySelector('#task_info')
if (!taskInfo) console.warn('Task info element not found')

const taskNameInput = taskInfo.querySelector('#name')
const taskStartDateTimeInput = taskInfo.querySelector('#start_date_time')
const taskCompletionDateTimeInput = taskInfo.querySelector('#completion_date_time')
const taskDescriptionInput = taskInfo.querySelector('#description')
const taskEstimatedCostInput = taskInfo.querySelector('#estimated_cost')
const taskBudgetNoteInput = taskInfo.querySelector('#budget_note')
if (!taskNameInput || !taskStartDateTimeInput || !taskCompletionDateTimeInput
    || !taskDescriptionInput || !taskEstimatedCostInput || !taskBudgetNoteInput)
    console.warn('One or more task info input elements not found')

taskNameInput?.addEventListener('input', () => {
    validateAndUpdate(
        taskNameInput,
        validateName(taskNameInput.value),
        RULE_MAPPINGS.workName
    )
})

taskStartDateTimeInput?.addEventListener('input', () => {
    validateAndUpdate(
        taskStartDateTimeInput,
        validateStartDateTime(taskStartDateTimeInput.value, {
            'isBounded': true,
            'boundStart': phaseStartDateTime,
            'boundCompletion': phaseCompletionDateTime
        }),
        RULE_MAPPINGS.startDateTime
    )
})

taskCompletionDateTimeInput?.addEventListener('input', () => {
    validateAndUpdate(
        taskCompletionDateTimeInput,
        validateCompletionDateTime(
            taskCompletionDateTimeInput.value,
            taskStartDateTimeInput.value,
            boundObject
        ),
        RULE_MAPPINGS.completionDateTime
    )
})

taskDescriptionInput?.addEventListener('input', () => {
    validateAndUpdate(
        taskDescriptionInput,
        validateDescription(taskDescriptionInput.value),
        RULE_MAPPINGS.workDescription
    )
})

taskEstimatedCostInput?.addEventListener('change', () => {
    validateAndUpdate(
        taskEstimatedCostInput,
        validateBudget(taskEstimatedCostInput.value, phaseBudget),
        RULE_MAPPINGS.workBudget,
        () => {
            // Revalidate all workers when task budget changes
            revalidateAllWorkers()
        }
    )
})

taskBudgetNoteInput?.addEventListener('input', () => {
    validateAndUpdate(
        taskBudgetNoteInput,
        validateBudgetNote(taskBudgetNoteInput.value),
        RULE_MAPPINGS.budgetNote
    )
})

/**
 * END
 * 
 * Worker Info
 */

const workerInfo = taskForm.querySelector('#worker_info')
if (!workerInfo) console.warn('Worker info element not found')

/**
 * Calculate total spending across all workers
 */
function calculateTotalWorkerSpending() {
    const workerCards = workerInfo?.querySelectorAll('.selected-task-worker-form-card') || []
    let total = 0

    workerCards.forEach(card => {
        const unitRate = parseFloat(card.querySelector('.unit-rate-input')?.value || 0)
        const hours = parseFloat(card.querySelector('.hours-assigned-input')?.value || 0)
        total += unitRate * hours
    })

    return total
}

/**
 * Helper function to validate worker input fields
 */
function validateWorkerInput(input, value, id, fieldType) {
    value = parseFloat(value)

    const isUnitRate = fieldType === 'unit-rate'
    const card = input.closest('.selected-task-worker-form-card')
    
    const unitRateInput = card.querySelector('.unit-rate-input')
    const hoursAssignedInput = card.querySelector('.hours-assigned-input')
    
    const rateValue = parseFloat(isUnitRate ? value : unitRateInput.value)
    const hoursValue = parseFloat(isUnitRate ? hoursAssignedInput.value : value)
    const totalSpending = rateValue * (hoursValue || 0)

    // Recalculate total from ALL worker cards
    const totalUnitRateSpending = calculateTotalWorkerSpending()

    const isValidNumber = /^[-+]?\d*\.?\d+$/.test(value)
    const minValue = isUnitRate ? VALIDATION_CONSTANTS.DEFAULT_RATE_MIN : VALIDATION_CONSTANTS.WORKER_HOURS_MIN
    const maxValue = isUnitRate ? VALIDATION_CONSTANTS.DEFAULT_RATE_MAX : VALIDATION_CONSTANTS.WORKER_HOURS_MAX
    const withinRange = value >= minValue && value <= maxValue
    
    // Check if TOTAL spending exceeds task budget
    const taskEstimatedCost = parseFloat(taskEstimatedCostInput?.value || 0)
    const withinTaskBudget = totalUnitRateSpending <= taskEstimatedCost

    if (!isValidNumber || !withinRange || !withinTaskBudget) {
        let errorMessage = ''
        
        if (!isValidNumber)
            errorMessage = 'Please enter a valid number'
        else if (!withinRange)
            errorMessage = `Value must be between ${minValue} and ${maxValue}`
        else if (!withinTaskBudget)
            errorMessage = `Total worker cost (₱${totalUnitRateSpending.toFixed(2)}) exceeds task budget (₱${taskEstimatedCost.toFixed(2)})`

        Notification.error(errorMessage, 5000)

        toggleElementClass(input, ['shake', 'invalid'], [])
        input.addEventListener('animationend', () => {
            toggleElementClass(input, [], ['shake'])
        }, { once: true })

        updateSubmitTaskButtonState(`${id}-${fieldType}`, true)
    } else {
        toggleElementClass(input, [], ['shake', 'invalid'])
        updateSubmitTaskButtonState(`${id}-${fieldType}`, false)
    }
}

/**
 * Revalidate all workers when task budget changes
 */
function revalidateAllWorkers() {
    const workerCards = workerInfo?.querySelectorAll('.selected-task-worker-form-card') || []
    workerCards.forEach(card => {
        const unitRateInput = card.querySelector('.unit-rate-input')
        const hoursAssignedInput = card.querySelector('.hours-assigned-input')
        const id = card.dataset.workerid

        if (unitRateInput?.value) {
            validateWorkerInput(unitRateInput, unitRateInput.value, id, 'unit-rate')
        }
        if (hoursAssignedInput?.value) {
            validateWorkerInput(hoursAssignedInput, hoursAssignedInput.value, id, 'hours-assigned')
        }
    })
}

workerInfo?.addEventListener('change', e => {
    const card = e.target.closest('.selected-task-worker-form-card')
    if (!card) return
    const id = card.dataset.workerid

    const unitRateInput = card.querySelector('.unit-rate-input')
    if (!unitRateInput) {
        console.warn('Unit rate input element not found')
        return
    }

    if (e.target === unitRateInput) 
        validateWorkerInput(unitRateInput, unitRateInput.value, id, 'unit-rate')
})

workerInfo?.addEventListener('change', e => {
    const card = e.target.closest('.selected-task-worker-form-card')
    if (!card) return
    const id = card.dataset.workerid

    const hoursAssignedInput = card.querySelector('.hours-assigned-input')
    if (!hoursAssignedInput) {
        console.warn('Hours assigned input element not found')
        return
    }

    if (e.target === hoursAssignedInput)
        validateWorkerInput(hoursAssignedInput, hoursAssignedInput.value, id, 'hours-assigned')
})

/**
 * END
 * 
 * Disable form submission if there are validation errors
 */

const submitTaskButton = taskForm.querySelector('.submit-task-button')
if (!submitTaskButton) console.warn('Submit task button not found')

function updateSubmitTaskButtonState(id, hasInvalid) {
    if (!submitTaskButton) return

    errorsMap[id] = hasInvalid

    let hasGlobalInvalid = Object.values(errorsMap).some(entry => entry === true)
    if (hasGlobalInvalid) {
        submitTaskButton.disabled = true
        submitTaskButton.title = 'Please fix validation errors before submitting the form.'
    } else {
        submitTaskButton.disabled = false
        submitTaskButton.title = ''
    }
}