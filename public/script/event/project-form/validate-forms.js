import {
    validateName,
    applyValidationToRules,
    RULE_MAPPINGS,
    validateDescription,
    validateBudget,
    validateWorkerCount,
    validateStartDateTime,
    validateCompletionDateTime
} from '../../utility/work-rules-validators.js'

const projectForm = document.querySelector('#project_form')
if (!projectForm) {
    console.warn('Project form not found on the page')
}

/**
 * Project Info
 */
const infoSection = projectForm.querySelector('#info_section')
if (!infoSection) {
    console.warn('Info section not found in the form')
}

const projectNameInput = infoSection.querySelector('input[name="name"]')
const projectDescriptionInput = infoSection.querySelector('textarea[name="description"]')
const projectBudgetInput = infoSection.querySelector('input[name="budget"]')
const projectMaxWorkersInput = infoSection.querySelector('input[name="max_workers"]')
const projectStartDateTimeInput = infoSection.querySelector('input[name="start_date_time"]')
const projectCompletionDateTimeInput = infoSection.querySelector('input[name="completion_date_time"]')
if (!projectNameInput || !projectDescriptionInput || !projectBudgetInput 
    || !projectMaxWorkersInput || !projectStartDateTimeInput || !projectCompletionDateTimeInput) {
    console.warn('One or more project info inputs not found in the form')
}

// Validation

projectNameInput.addEventListener('input', () => {
    const results = validateName(projectNameInput.value)
    const rulesContainer = projectNameInput.closest('.input-rules-container').querySelector('.rules ul')
    applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workName)
})

projectDescriptionInput.addEventListener('input', () => {
    const results = validateDescription(projectDescriptionInput.value)
    const rulesContainer = projectDescriptionInput.closest('.input-rules-container').querySelector('.rules ul')
    applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workDescription)
})

projectBudgetInput.addEventListener('input', () => {
    const results = validateBudget(projectBudgetInput.value)
    const rulesContainer = projectBudgetInput.closest('.input-rules-container').querySelector('.rules ul')
    applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workBudget)
})

projectMaxWorkersInput.addEventListener('input', () => {
    const results = validateWorkerCount(projectMaxWorkersInput.value)
    const rulesContainer = projectMaxWorkersInput.closest('.input-rules-container').querySelector('.rules ul')
    applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workerCount)
})

projectStartDateTimeInput.addEventListener('input', () => {
    const results = validateStartDateTime(projectStartDateTimeInput.value)
    const rulesContainer = projectStartDateTimeInput.closest('.input-rules-container').querySelector('.rules ul')
    applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.startDateTime)
})

projectCompletionDateTimeInput.addEventListener('input', () => {
    const results = validateCompletionDateTime(projectCompletionDateTimeInput.value, projectStartDateTimeInput.value)
    const rulesContainer = projectCompletionDateTimeInput.closest('.input-rules-container').querySelector('.rules ul')
    applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.completionDateTime)
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

phaseSection.addEventListener('input', e => {
    const card = e.target.closest('.phase-form-card')
    if (!card) {
        return
    }

    const phaseNameInput = card.querySelector('input[name="name"]')
    const phaseDescriptionInput = card.querySelector('textarea[name="description"]')
    const phaseBudgetInput = card.querySelector('input[name="budget"]')
    const phaseContingencyRateInput = card.querySelector('input[name="contingency_rate"]')
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
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workName)
    }

    if (e.target === phaseDescriptionInput) {
        const results = validateDescription(phaseDescriptionInput.value)
        const rulesContainer = phaseDescriptionInput.closest('.input-rules-container').querySelector('.rules ul')
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workDescription)
    }

    if (e.target === phaseBudgetInput) {
        const results = validateBudget(phaseBudgetInput.value)
        const rulesContainer = phaseBudgetInput.closest('.input-rules-container').querySelector('.rules ul')
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workBudget)
    }

    if (e.target === phaseContingencyRateInput) {
        const results = validateCompletionDateTime(phaseContingencyRateInput.value)
        const rulesContainer = phaseContingencyRateInput.closest('.input-rules-container').querySelector('.rules ul')
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.contingencyRate)
    }

    if (e.target === phaseStartDateTimeInput) {
        const results = validateStartDateTime(phaseStartDateTimeInput.value)
        const rulesContainer = phaseStartDateTimeInput.closest('.input-rules-container').querySelector('.rules ul')
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.startDateTime)
    }

    if (e.target === phaseCompletionDateTimeInput) {
        const results = validateCompletionDateTime(phaseCompletionDateTimeInput.value, phaseStartDateTimeInput.value)
        const rulesContainer = phaseCompletionDateTimeInput.closest('.input-rules-container').querySelector('.rules ul')
        applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.completionDateTime)
    }
})

