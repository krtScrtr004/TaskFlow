import { createFullName, getValidationConstraints, toggleElementClass } from '../../utility/utility.js'
import { selectedUsers } from './select.js'

const ICON_PATH = '/public/asset/image/icon/'

const VALIDATION_CONSTRAINTS = await getValidationConstraints()
if (!VALIDATION_CONSTRAINTS)
    console.warn('Failed to load validation constraints')

/**
 * Creates and appends a worker card to the worker list.
 * 
 * @param {Object} worker - The worker object containing id, firstName, lastName, profileLink, jobTitles
 * @param {HTMLElement|null} workerListContainer - Optional container for the worker list
 */
export function createWorkerListCard(worker, workerListContainer = null) {
    const workerList = workerListContainer || document.querySelector('#add_worker_modal_template .worker-list > .list')
    if (!workerList) {
        console.error('Worker list container not found.')
        return
    }

    // Create main container div
    const workerCheckbox = document.createElement('div')
    workerCheckbox.className = 'worker-checkbox flex-row flex-child-center-h'

    // Create checkbox input
    const checkbox = document.createElement('input')
    checkbox.type = 'checkbox'
    checkbox.name = worker.id
    checkbox.id = worker.id
    checkbox.checked = selectedUsers.has(worker.id)
    workerCheckbox.appendChild(checkbox)

    // Create label
    const label = document.createElement('label')
    label.htmlFor = worker.id
    label.className = 'user-list-card'
    label.dataset.id = worker.id

    // Create image container
    const imgContainer = document.createElement('div')
    imgContainer.className = 'flex-col flex-child-center-v'

    const img = document.createElement('img')
    img.src = worker.profileLink || ICON_PATH + 'profile_w.svg'
    img.className = 'circle fit-cover'
    img.alt = createFullName(worker.firstName, worker.middleName, worker.lastName) || 'Profile Picture'
    img.title = createFullName(worker.firstName, worker.middleName, worker.lastName) || 'Profile Picture'
    img.height = 40
    imgContainer.appendChild(img)

    // Create info container
    const infoContainer = document.createElement('div')
    infoContainer.className = 'flex-col'

    // Create name and ID section
    const nameSection = document.createElement('div')

    const nameHeader = document.createElement('h4')
    nameHeader.className = 'wrap-text'
    nameHeader.textContent = createFullName(worker.firstName, worker.middleName, worker.lastName) || ''
    nameSection.appendChild(nameHeader)

    const idPara = document.createElement('p')
    const idEm = document.createElement('em')
    idEm.className = 'id'
    idEm.textContent = worker.id
    idPara.appendChild(idEm)
    nameSection.appendChild(idPara)

    // Create job titles section
    const jobTitlesDiv = document.createElement('div')
    jobTitlesDiv.className = 'job-titles flex-row flex-wrap'

    if (worker.jobTitles && worker.jobTitles.length > 0) {
        worker.jobTitles.forEach(title => {
            const span = document.createElement('span')
            span.className = 'job-title-chip'
            span.textContent = title
            jobTitlesDiv.appendChild(span)
        })
    } else {
        const noJobSpan = document.createElement('span')
        noJobSpan.className = 'no-job-title-badge'
        noJobSpan.textContent = 'No Job Titles'
        jobTitlesDiv.appendChild(noJobSpan)
    }

    // Assemble the components
    infoContainer.appendChild(nameSection)
    infoContainer.appendChild(jobTitlesDiv)

    label.appendChild(imgContainer)
    label.appendChild(infoContainer)

    workerCheckbox.appendChild(label)

    workerList.appendChild(workerCheckbox)
}

/*-----------------------------------------------------------------------------------------------------------------------*/

/** Renders a selected worker row for the selected workers table.
 * 
 * @param {Object} worker - The worker object containing id, firstName, lastName, profileLink, defaultRate
 * @returns {HTMLElement} The table row element representing the selected worker
 */
export function renderSelectedWorkerRow(worker) {
    const id = worker.id || ''
    const tr = document.createElement('tr')
    tr.dataset.workerid = id

    tr.appendChild(createProfileCell(worker))
    tr.appendChild(createRateCell(worker))
    tr.appendChild(createRemoveCell())

    return tr
}

/* Helper: profile cell with image and name */
function createProfileCell(worker) {
    const id = worker.id || ''
    const name = createFullName(worker.firstName, worker.middleName, worker.lastName) || ''
    const profileLink = worker.profileLink || ICON_PATH + 'worker_w.svg'

    const td = document.createElement('td')
    const profileWrap = document.createElement('div')
    profileWrap.className = 'worker-profile-name flex-row flex-child-center-h'

    const img = document.createElement('img')
    img.className = 'fit-contain circle'
    img.src = profileLink
    img.alt = name
    img.height = 40

    const nameP = document.createElement('p')
    nameP.className = 'single-line-ellipsis'
    nameP.textContent = name

    profileWrap.appendChild(img)
    profileWrap.appendChild(nameP)
    td.appendChild(profileWrap)

    return td
}

/* Helper: rate cell with input */
function createRateCell(worker) {
    const id = worker.id || ''
    const defaultRate = worker.defaultRate ?? ''
    const td = document.createElement('td')

    const rateWrap = document.createElement('div')
    rateWrap.className = 'input-w-prefix'

    const prefix = document.createElement('span')
    prefix.className = 'input-prefix'
    prefix.textContent = '₱'

    const input = document.createElement('input')
    input.type = 'number'
    // make input identifiers unique per worker to avoid collisions
    input.name = `default_rate_${id}`
    input.id = `default_rate_${id}`
    input.placeholder = String(VALIDATION_CONSTRAINTS?.DEFAULT_RATE_MIN ?? 0.00)
    input.value = defaultRate
    input.step = 0.01
    if (VALIDATION_CONSTRAINTS) {
        if (VALIDATION_CONSTRAINTS.DEFAULT_RATE_MIN != null) input.min = VALIDATION_CONSTRAINTS.DEFAULT_RATE_MIN
        if (VALIDATION_CONSTRAINTS.DEFAULT_RATE_MAX != null) input.max = VALIDATION_CONSTRAINTS.DEFAULT_RATE_MAX
    }
    input.required = true

    rateWrap.appendChild(prefix)
    rateWrap.appendChild(input)
    td.appendChild(rateWrap)

    return td
}

/* Helper: remove button cell */
function createRemoveCell() {
    const td = document.createElement('td')
    const spanCenter = document.createElement('span')
    spanCenter.className = 'center-child'

    const button = document.createElement('button')
    button.className = 'remove-worker-button unset-button'
    button.type = 'button'

    const delImg = document.createElement('img')
    delImg.src = ICON_PATH + 'delete_r.svg'
    delImg.alt = 'Remove Worker'
    delImg.title = 'Remove Worker'
    delImg.height = 24

    button.appendChild(delImg)
    spanCenter.appendChild(button)
    td.appendChild(spanCenter)

    return td
}

/*-----------------------------------------------------------------------------------------------------------------------*/

/**
 * Creates a selected task worker card element for the create task form.
 * 
 * This function generates a styled card displaying worker information including their full name,
 * unit rate, and estimated hours assigned. The card includes input fields for modifying
 * the unit rate and hours assigned, as well as a button to remove the worker from the selection.
 * 
 * Behavior and side effects:
 * - The generated card contains a data-workerid attribute for JavaScript interaction.
 * - Input fields for unit rate and hours assigned are marked as required and have class names for targeting.
 * - The full name input field is disabled (display only).
 * - The remove button includes a click handler that removes the card with animation.
 * 
 * @param {Object} taskWorker - The task worker object containing id, fullName, unitRate, estimatedHoursAssigned
 * @returns {HTMLElement} The generated card element
 */
export function renderSelectedTaskWorkerCard(taskWorker) {
    const iconPath = '/public/asset/image/icon/'
    const { id, fullName, unitRate, estimatedHoursAssigned } = taskWorker

    const card = createCardContainer(id)
    card.appendChild(createFullNameField(fullName, iconPath))
    card.appendChild(createMultipleInputsRow(unitRate, estimatedHoursAssigned, iconPath))
    card.appendChild(createRemoveButton(id, card))

    return card
}

/* Helper: card container */
function createCardContainer(workerId) {
    const card = document.createElement('div')
    card.className = 'selected-task-worker-form-card light-black-bg flex-col'
    card.dataset.workerid = workerId
    return card
}

/* Helper: build labeled input container with icon */
function createLabeledInput({ labelText, iconFile, iconAlt = '', inputType = 'text', inputAttrs = {}, inputClass = '', containerClass = 'input-label-container' }, iconPath) {
    const container = document.createElement('div')
    container.className = containerClass

    const label = document.createElement('label')
    const labelInner = document.createElement('div')
    labelInner.className = 'text-w-icon'

    const img = document.createElement('img')
    img.src = iconPath + iconFile
    img.alt = iconAlt || labelText
    img.title = iconAlt || labelText
    img.height = 16

    const text = document.createElement('p')
    text.textContent = labelText

    labelInner.appendChild(img)
    labelInner.appendChild(text)
    label.appendChild(labelInner)

    const input = document.createElement('input')
    input.type = inputType
    if (inputClass) input.className = inputClass
    Object.entries(inputAttrs).forEach(([k, v]) => {
        if (k === 'value') input.value = v
        else if (k === 'disabled' && v) input.disabled = true
        else input.setAttribute(k, String(v))
    })

    container.appendChild(label)
    container.appendChild(input)

    return { container, input }
}

/* Full name field (disabled text input) */
function createFullNameField(fullName, iconPath) {
    const { container } = createLabeledInput({
        labelText: 'Full Name',
        iconFile: 'name_w.svg',
        inputType: 'text',
        inputAttrs: { placeholder: 'Full Name', value: fullName ?? '', disabled: true }
    }, iconPath)
    return container
}

/* Multiple inputs row containing rate and hours */
function createMultipleInputsRow(unitRate, estimatedHoursAssigned, iconPath) {
    const row = document.createElement('div')
    row.className = 'multiple-input-row'

    const rate = createLabeledInput({
        labelText: 'Unit Rate',
        iconFile: 'rate_w.svg',
        inputType: 'number',
        inputClass: 'unit-rate-input',
        inputAttrs: { step: '0.01', placeholder: 'Unit Rate', required: true, value: unitRate ?? '' }
    }, iconPath)

    const hours = createLabeledInput({
        labelText: 'Hours Assigned',
        iconFile: 'clock_w.svg',
        inputType: 'number',
        inputClass: 'hours-assigned-input',
        inputAttrs: { step: '0.01', placeholder: 'Hours Assigned', required: true, value: estimatedHoursAssigned ?? '' },
        containerClass: 'estimated-hours input-label-container'
    }, iconPath)

    row.appendChild(rate.container)
    row.appendChild(hours.container)
    return row
}

/* Remove button */
function createRemoveButton(workerId, cardElement) {
    const btn = document.createElement('button')
    btn.className = 'remove-worker-button unset-button'
    btn.type = 'button'

    const removeImg = document.createElement('img')
    removeImg.src = '/public/asset/image/icon/delete_r.svg'
    removeImg.alt = 'Remove Worker'
    removeImg.title = 'Remove Worker'
    removeImg.height = 18

    btn.appendChild(removeImg)

    return btn
}
