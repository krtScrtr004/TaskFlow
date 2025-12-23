import { handleException } from '../../utility/handle-exception.js'
import { getValidationConstraints, die } from '../../utility/utility.js'
import { addedPhases, changedPhases, removedPhases } from './record-changes.js'

const VALIDATION_CONSTANTS = await getValidationConstraints()

const phaseSection = document.querySelector('#phase_section')
const noPhasesWall = phaseSection.querySelector('.no-phases-wall')

const addPhaseButton = phaseSection.querySelector('#add_phase_button')
if (!addPhaseButton) {
    die('Clone Phase Card: Add Phase button not found.')
}

addPhaseButton.addEventListener('click', () => {
    try {
        const phaseCard = renderPhaseFormCard()
        phaseSection.insertBefore(phaseCard, addPhaseButton)

        const {
            phaseNameInput,
            phaseDescriptionInput,
            phaseBudgetInput,
            phaseContingencyRateInput,
            phaseBudgetNoteInput,
            phaseStartDateTimeInput,
            phaseCompletionDateTimeInput
        } = getPhaseDomParts(card) ?? {}
        addedPhases.set(id, {
            name: phaseNameInput.value.trim() || '',
            description: phaseDescriptionInput.value.trim() || '',
            budget: phaseBudgetInput.value.trim() || '',
            contingencyRate: phaseContingencyRateInput.value.trim() || '',
            budgetNote: phaseBudgetNoteInput.value.trim() || '',
            startDateTime: phaseStartDateTimeInput.value.trim() || '',
            completionDateTime: phaseCompletionDateTimeInput.value.trim() || ''
        })

        const newCard = phaseSection.querySelector('.phase-form-card:last-of-type')
        addRemoveListeners(newCard)

        // Hide no phases wall
        noPhasesWall?.classList.add('no-display')
        noPhasesWall?.classList.remove('flex-col')
    } catch (error) {
        handleException(error, `Error cloning phase card.`)
    }
})

/**
 * Attaches a click listener to the remove button inside a phase card.
 *
 * Locates the '.remove-phase-button' within the provided card and, if found,
 * registers a click handler that:
 *  - removes the card from the DOM,
 *  - queries the outer-scope `phaseSection` for remaining '.phase-form-card' elements,
 *  - if none remain, makes the `noPhasesWall` visible by removing 'no-display' and
 *    adding 'flex-col'.
 *
 * If the remove button is not found, a warning is logged and no listener is attached.
 * Note: this function relies on the presence of `phaseSection` and `noPhasesWall` in
 * the surrounding scope and does not return a value.
 *
 * @param {Element|HTMLElement} card - The DOM element representing a phase form card.
 * @returns {void}
 */
function addRemoveListeners(card) {
    const removeButton = card.querySelector('.remove-phase-button')
    if (!removeButton) {
        die('Remove button not found in card.')
    }

    removeButton.addEventListener('click', () => {
        card.classList.add('fade-out')
        // Wait for animation to finish before removing
        card.addEventListener('animationend', () => {
            card.remove()

            // Check if any phase cards remain, show no phases wall if none
            const remainingCards = phaseSection.querySelectorAll('.phase-form-card')
            if (remainingCards.length === 0) {
                noPhasesWall?.classList.remove('no-display')
                noPhasesWall?.classList.add('flex-col')
            }
        })
    })

    const id = card.dataset.phaseid
    addedPhases.delete(id)
    changedPhases.delete(id)
    removedPhases.add(id)
}

/**
 * Renders a phase form card element with all input fields.
 *
 * @param {Object} [phaseData={}] - Optional phase data to pre-populate the form.
 * @param {string} [phaseData.id] - The phase ID.
 * @param {string} [phaseData.name] - The phase name.
 * @param {string} [phaseData.description] - The phase description.
 * @param {string|number} [phaseData.budget] - The phase budget.
 * @param {string|number} [phaseData.contingencyRate] - The contingency rate.
 * @param {string} [phaseData.budgetNote] - The budget note.
 * @param {string} [phaseData.startDateTime] - The start date (YYYY-MM-DD).
 * @param {string} [phaseData.completionDateTime] - The completion date (YYYY-MM-DD).
 * @returns {HTMLDivElement} The rendered phase form card element.
 */
function renderPhaseFormCard(phaseData = {}) {
    const ICON_PATH = '/public/asset/image/icon/'

    const defaults = {
        id: Math.random().toString(36).substring(2, 15), // Generate a random temporary id for the phase card
        name: '',
        description: '',
        budget: '',
        contingencyRate: '',
        budgetNote: '',
        startDateTime: '',
        completionDateTime: '',
    }
    const data = { ...defaults, ...phaseData }

    // Main card container
    const card = document.createElement('div')
    card.className = 'phase-form-card flex-col fade-in'
    card.dataset.phaseid = data.id

    // Heading section
    const heading = document.createElement('section')
    heading.className = 'heading flex-row flex-space-between black-bg'

    const textWithIcon = document.createElement('div')
    textWithIcon.className = 'text-w-icon'

    const phaseIcon = document.createElement('img')
    phaseIcon.src = ICON_PATH + 'phase_w.svg'
    phaseIcon.alt = 'Phase'
    phaseIcon.title = 'Phase'
    phaseIcon.height = 24

    const headingTitle = document.createElement('h2')
    headingTitle.textContent = 'New Project Phase'

    textWithIcon.appendChild(phaseIcon)
    textWithIcon.appendChild(headingTitle)

    const removeButton = document.createElement('button')
    removeButton.className = 'remove-phase-button unset-button'
    removeButton.type = 'button'

    const removeIcon = document.createElement('img')
    removeIcon.src = ICON_PATH + 'delete_r.svg'
    removeIcon.alt = 'Remove'
    removeIcon.height = 24
    removeButton.appendChild(removeIcon)

    heading.appendChild(textWithIcon)
    heading.appendChild(removeButton)

    // Inputs section
    const inputsSection = document.createElement('section')
    inputsSection.className = 'inputs-section flex-col'

    // Name input with rules
    inputsSection.appendChild(createInputWithRules({
        iconSrc: ICON_PATH + 'name_w.svg',
        iconAlt: 'Name',
        label: 'Name',
        inputType: 'text',
        inputName: 'name',
        placeholder: '(eg. Requirement Analysis)',
        value: data.name,
        min: VALIDATION_CONSTANTS.NAME_MIN,
        max: VALIDATION_CONSTANTS.NAME_MAX,
        required: true,
        rules: renderWorkNameRules(),
    }))

    // Description textarea with rules
    inputsSection.appendChild(createTextareaWithRules({
        iconSrc: ICON_PATH + 'description_w.svg',
        iconAlt: 'Description',
        label: 'Description',
        inputName: 'description',
        placeholder: 'Describe the phase objectives, scope, and deliverables (optional)',
        value: data.description,
        rows: 4,
        min: VALIDATION_CONSTANTS.LONG_TEXT_MIN,
        max: VALIDATION_CONSTANTS.LONG_TEXT_MAX,
        required: true,
        rules: renderWorkDescriptionRules(),
    }))

    // Budget and Contingency Rate row
    const budgetRow = document.createElement('section')
    budgetRow.className = 'row-inputs flex-row'

    // Budget input with prefix and rules
    const budgetRulesContainer = document.createElement('div')
    budgetRulesContainer.className = 'input-rules-container'

    const budgetContainer = document.createElement('div')
    budgetContainer.className = 'input-label-container'

    const budgetTextIcon = document.createElement('div')
    budgetTextIcon.className = 'text-w-icon'

    const budgetIcon = document.createElement('img')
    budgetIcon.src = ICON_PATH + 'budget_w.svg'
    budgetIcon.alt = 'Budget'
    budgetIcon.title = 'Budget'
    budgetIcon.height = 24

    const budgetLabel = document.createElement('label')
    budgetLabel.htmlFor = 'budget'
    budgetLabel.textContent = 'Budget'

    budgetTextIcon.appendChild(budgetIcon)
    budgetTextIcon.appendChild(budgetLabel)

    const inputWithPrefix = document.createElement('div')
    inputWithPrefix.className = 'input-w-prefix'

    const prefixSpan = document.createElement('span')
    prefixSpan.className = 'input-prefix'
    prefixSpan.textContent = '₱'

    const budgetInput = document.createElement('input')
    budgetInput.type = 'number'
    budgetInput.name = 'budget'
    budgetInput.id = 'budget'
    budgetInput.value = data.budget
    budgetInput.placeholder = '0.00'
    budgetInput.required = true

    inputWithPrefix.appendChild(prefixSpan)
    inputWithPrefix.appendChild(budgetInput)

    budgetContainer.appendChild(budgetTextIcon)
    budgetContainer.appendChild(inputWithPrefix)

    budgetRulesContainer.appendChild(budgetContainer)
    budgetRulesContainer.appendChild(renderWorkBudgetRules())

    budgetRow.appendChild(budgetRulesContainer)

    // Contingency Rate input with rules
    budgetRow.appendChild(createInputWithRules({
        iconSrc: ICON_PATH + 'safe_w.svg',
        iconAlt: 'Contingency Rate',
        label: 'Contingency Rate',
        inputType: 'number',
        inputName: 'contingency_rate',
        placeholder: '0',
        value: data.contingencyRate,
        min: VALIDATION_CONSTANTS.CONTINGENCY_RATE_MIN,
        max: VALIDATION_CONSTANTS.CONTINGENCY_RATE_MAX,
        required: true,
        rules: renderWorkContingencyRateRules(),
    }))

    inputsSection.appendChild(budgetRow)

    // Budget Note textarea with rules
    inputsSection.appendChild(createTextareaWithRules({
        iconSrc: ICON_PATH + 'description_w.svg',
        iconAlt: 'Budget Note',
        label: 'Budget Note',
        inputName: 'budget_note',
        placeholder: 'Provide additional details about the budget allocation for this phase (optional)',
        value: data.budgetNote,
        rows: 4,
        min: VALIDATION_CONSTANTS.LONG_TEXT_MIN,
        max: VALIDATION_CONSTANTS.LONG_TEXT_MAX,
        required: true,
        rules: renderWorkBudgetNoteRules(),
    }))

    // Start and Completion Date row
    const dateRow = document.createElement('section')
    dateRow.className = 'row-inputs flex-row'

    dateRow.appendChild(createInputWithRules({
        iconSrc: ICON_PATH + 'start_w.svg',
        iconAlt: 'Start Date',
        label: 'Start Date',
        inputType: 'date',
        inputName: 'start_date_time',
        value: data.startDateTime,
        required: true,
        rules: renderWorkStartDateTimeRules(),
    }))

    dateRow.appendChild(createInputWithRules({
        iconSrc: ICON_PATH + 'complete_w.svg',
        iconAlt: 'Completion Date',
        label: 'End Date',
        inputType: 'date',
        inputName: 'completion_date_time',
        value: data.completionDateTime,
        required: true,
        rules: renderWorkCompletionDateTimeRules(),
    }))

    inputsSection.appendChild(dateRow)

    card.appendChild(heading)
    card.appendChild(inputsSection)

    return card
}

/**
 * Helper function to create an input container with label, icon, and rules wrapped in input-rules-container.
 */
function createInputWithRules({ iconSrc, iconAlt, label, inputType, inputName, placeholder = '', value = '', min, max, required = false, rules = null }) {
    const rulesContainer = document.createElement('div')
    rulesContainer.className = 'input-rules-container'

    const container = document.createElement('div')
    container.className = 'input-label-container'

    const textIcon = document.createElement('div')
    textIcon.className = 'text-w-icon'

    const icon = document.createElement('img')
    icon.src = iconSrc
    icon.alt = iconAlt
    icon.title = iconAlt
    icon.height = 24

    const labelEl = document.createElement('label')
    labelEl.htmlFor = inputName
    labelEl.textContent = label

    textIcon.appendChild(icon)
    textIcon.appendChild(labelEl)

    const input = document.createElement('input')
    input.type = inputType
    input.name = inputName
    input.id = inputName
    input.value = value
    if (placeholder) input.placeholder = placeholder
    if (min !== undefined) input.min = min
    if (max !== undefined) input.max = max
    if (required) input.required = true

    container.appendChild(textIcon)
    container.appendChild(input)

    rulesContainer.appendChild(container)
    if (rules) rulesContainer.appendChild(rules)

    return rulesContainer
}

/**
 * Helper function to create a textarea container with label, icon, and rules wrapped in input-rules-container.
 */
function createTextareaWithRules({ iconSrc, iconAlt, label, inputName, placeholder = '', value = '', rows = 4, min, max, required = false, rules = null }) {
    const rulesContainer = document.createElement('div')
    rulesContainer.className = 'input-rules-container'

    const container = document.createElement('div')
    container.className = 'input-label-container'

    const textIcon = document.createElement('div')
    textIcon.className = 'text-w-icon'

    const icon = document.createElement('img')
    icon.src = iconSrc
    icon.alt = iconAlt
    icon.title = iconAlt
    icon.height = 24

    const labelEl = document.createElement('label')
    labelEl.htmlFor = inputName
    labelEl.textContent = label

    textIcon.appendChild(icon)
    textIcon.appendChild(labelEl)

    const textarea = document.createElement('textarea')
    textarea.name = inputName
    textarea.id = inputName
    textarea.rows = rows
    textarea.placeholder = placeholder
    textarea.textContent = value
    if (min !== undefined) textarea.setAttribute('min', min)
    if (max !== undefined) textarea.setAttribute('max', max)
    if (required) textarea.required = true

    container.appendChild(textIcon)
    container.appendChild(textarea)

    rulesContainer.appendChild(container)
    if (rules) rulesContainer.appendChild(rules)

    return rulesContainer
}

/**
 * Renders the work name validation rules element.
 * @returns {HTMLDivElement}
 */
function renderWorkNameRules() {
    const rules = document.createElement('div')
    rules.className = 'rules'

    const ul = document.createElement('ul')
    ul.innerHTML = `
        <li>Must be between ${VALIDATION_CONSTANTS.NAME_MIN} and ${VALIDATION_CONSTANTS.NAME_MAX} characters.</li>
        <li>Must not contain three or more consecutive special characters.</li>
    `
    rules.appendChild(ul)
    return rules
}

/**
 * Renders the work description validation rules element.
 * @returns {HTMLDivElement}
 */
function renderWorkDescriptionRules() {
    const rules = document.createElement('div')
    rules.className = 'rules'

    const ul = document.createElement('ul')
    ul.innerHTML = `
        <li>Must be between ${VALIDATION_CONSTANTS.LONG_TEXT_MIN} and ${VALIDATION_CONSTANTS.LONG_TEXT_MAX} characters.</li>
        <li>Must not contain three or more consecutive special characters.</li>
    `
    rules.appendChild(ul)
    return rules
}

/**
 * Renders the work budget validation rules element.
 * @returns {HTMLDivElement}
 */
function renderWorkBudgetRules() {
    const rules = document.createElement('div')
    rules.className = 'rules'

    const ul = document.createElement('ul')
    ul.innerHTML = `
        <li>Must be a positive number.</li>
        <li>Maximum budget is ₱${VALIDATION_CONSTANTS.BUDGET_MAX.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}.</li>
        <li>Have up to two decimal places.</li>
        <li>Total budget must not exceed the Project budget.</li>
    `
    rules.appendChild(ul)
    return rules
}

/**
 * Renders the contingency rate validation rules element.
 * @returns {HTMLDivElement}
 */
function renderWorkContingencyRateRules() {
    const rules = document.createElement('div')
    rules.className = 'rules'

    const ul = document.createElement('ul')
    ul.innerHTML = `
        <li>Must be a number between ${VALIDATION_CONSTANTS.CONTINGENCY_RATE_MIN} and ${VALIDATION_CONSTANTS.CONTINGENCY_RATE_MAX}.</li>
    `
    rules.appendChild(ul)
    return rules
}

/**
 * Renders the budget note validation rules element.
 * @returns {HTMLDivElement}
 */
function renderWorkBudgetNoteRules() {
    const rules = document.createElement('div')
    rules.className = 'rules'

    const ul = document.createElement('ul')
    ul.innerHTML = `
        <li>Must be between ${VALIDATION_CONSTANTS.LONG_TEXT_MIN} and ${VALIDATION_CONSTANTS.LONG_TEXT_MAX} characters.</li>
        <li>Must not contain three or more consecutive special characters.</li>
    `
    rules.appendChild(ul)
    return rules
}

/**
 * Renders the start date/time validation rules element.
 * @returns {HTMLDivElement}
 */
function renderWorkStartDateTimeRules() {
    const rules = document.createElement('div')
    rules.className = 'rules'

    const ul = document.createElement('ul')
    ul.innerHTML = `
        <li>Must be a valid date.</li>
        <li>Must be between ${VALIDATION_CONSTANTS.YEAR_CURRENT} and ${VALIDATION_CONSTANTS.YEAR_MAX}.</li>
        <li>Must be within the timeline of Project.</li>
        <li>Must not conflict with other Phases.</li>
    `
    rules.appendChild(ul)
    return rules
}

/**
 * Renders the completion date/time validation rules element.
 * @returns {HTMLDivElement}
 */
function renderWorkCompletionDateTimeRules() {
    const rules = document.createElement('div')
    rules.className = 'rules'

    const ul = document.createElement('ul')
    ul.innerHTML = `
        <li>Must be a valid date.</li>
        <li>Must be between ${VALIDATION_CONSTANTS.YEAR_CURRENT} and ${VALIDATION_CONSTANTS.YEAR_MAX}.</li>
        <li>Must be after the start date.</li>
        <li>Must be within the timeline of Project.</li>
        <li>Must not conflict with other Phases.</li>
    `
    rules.appendChild(ul)
    return rules
}
