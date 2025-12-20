import { handleException } from '../../utility/handle-exception.js'

const phaseSection = document.querySelector('#phase_section')
const noPhasesWall = phaseSection.querySelector('.no-phases-wall')

const addPhaseButton = phaseSection.querySelector('#add_phase_button')
if (!addPhaseButton) {
    console.warn('Clone Phase Card: Add Phase button not found.')
}

addPhaseButton?.addEventListener('click', () => {
    try {
        const phaseCard = renderPhaseFormCard()
        phaseSection.insertBefore(phaseCard, addPhaseButton)

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
        console.warn('Clone Phase Card: Remove button not found in card.')
        return
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
    const today = new Date().toISOString().split('T')[0]

    const defaults = {
        id: Math.random().toString(36).substring(2, 15), // Generate a random temporary id for the phase card
        name: '',
        description: '',
        budget: '',
        contingencyRate: '',
        budgetNote: '',
        startDateTime: today,
        completionDateTime: today,
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

    // Name input
    inputsSection.appendChild(createInputContainer({
        iconSrc: ICON_PATH + 'name_w.svg',
        iconAlt: 'Name',
        label: 'Name',
        inputType: 'text',
        inputName: 'name',
        placeholder: '(eg. Requirement Analysis)',
        value: data.name,
        required: true,
    }))

    // Description textarea
    inputsSection.appendChild(createTextareaContainer({
        iconSrc: ICON_PATH + 'description_w.svg',
        iconAlt: 'Description',
        label: 'Description',
        inputName: 'description',
        placeholder: 'Describe the phase objectives, scope, and deliverables (optional)',
        value: data.description,
        rows: 4,
        required: true,
    }))

    // Budget and Contingency Rate row
    const budgetRow = document.createElement('section')
    budgetRow.className = 'row-inputs flex-row'

    // Budget input with prefix
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

    // Contingency Rate input
    budgetRow.appendChild(budgetContainer)
    budgetRow.appendChild(createInputContainer({
        iconSrc: ICON_PATH + 'safe_w.svg',
        iconAlt: 'Contingency Rate',
        label: 'Contingency Rate',
        inputType: 'number',
        inputName: 'contingency_rate',
        placeholder: '0',
        value: data.contingencyRate,
        min: 0,
        max: 100,
        required: true,
    }))

    inputsSection.appendChild(budgetRow)

    // Budget Note textarea
    inputsSection.appendChild(createTextareaContainer({
        iconSrc: ICON_PATH + 'description_w.svg',
        iconAlt: 'Budget Note',
        label: 'Budget Note',
        inputName: 'budget_note',
        placeholder: 'Provide additional details about the budget allocation for this phase (optional)',
        value: data.budgetNote,
        rows: 4,
        required: true,
    }))

    // Start and Completion Date row
    const dateRow = document.createElement('section')
    dateRow.className = 'row-inputs flex-row'

    dateRow.appendChild(createInputContainer({
        iconSrc: ICON_PATH + 'start_w.svg',
        iconAlt: 'Start Date',
        label: 'Start Date',
        inputType: 'date',
        inputName: 'start_date_time',
        value: data.startDateTime,
        required: true,
    }))

    dateRow.appendChild(createInputContainer({
        iconSrc: ICON_PATH + 'complete_w.svg',
        iconAlt: 'Completion Date',
        label: 'End Date',
        inputType: 'date',
        inputName: 'completion_date_time',
        value: data.completionDateTime,
        required: true,
    }))

    inputsSection.appendChild(dateRow)

    card.appendChild(heading)
    card.appendChild(inputsSection)

    return card
}

/**
 * Helper function to create an input container with label and icon.
 */
function createInputContainer({ iconSrc, iconAlt, label, inputType, inputName, placeholder = '', value = '', min, max, required = false }) {
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

    return container
}

/**
 * Helper function to create a textarea container with label and icon.
 */
function createTextareaContainer({ iconSrc, iconAlt, label, inputName, placeholder = '', value = '', rows = 4, required = false }) {
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
    if (required) textarea.required = true

    container.appendChild(textIcon)
    container.appendChild(textarea)

    return container
}
