import { createFullName, getValidationConstraints } from '../../utility/utility.js'
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

export function renderSelectedWorkerRow(worker) {
    const id = worker.id || ''
    const name = createFullName(worker.firstName, worker.middleName, worker.lastName) || ''
    const defaultRate = worker.defaultRate ?? ''
    const profileLink = worker.profileLink || ICON_PATH + 'worker_w.svg'

    const tr = document.createElement('tr')
    tr.dataset.workerid = id

    // First cell: profile + name
    const tdProfile = document.createElement('td')
    const profileWrap = document.createElement('div')
    profileWrap.className = 'worker-profile-name flex-row flex-child-center-h'

    const img = document.createElement('img')
    img.className = 'fit-contain circle'
    img.src = profileLink
    img.alt = ''
    img.height = 40

    const nameP = document.createElement('p')
    nameP.className = 'single-line-ellipsis'
    nameP.textContent = name

    profileWrap.appendChild(img)
    profileWrap.appendChild(nameP)
    tdProfile.appendChild(profileWrap)

    // Second cell: default rate input with prefix
    const tdRate = document.createElement('td')
    const rateWrap = document.createElement('div')
    rateWrap.className = 'input-w-prefix'

    const prefix = document.createElement('span')
    prefix.className = 'input-prefix'
    prefix.textContent = '₱'

    const input = document.createElement('input')
    input.type = 'number'
    input.name = 'default_rate'
    input.id = 'default_rate'
    input.placeholder = VALIDATION_CONSTRAINTS.DEFAULT_RATE_MIN ?? 0.00
    input.value = defaultRate
    input.step = 0.01
    input.min = VALIDATION_CONSTRAINTS.DEFAULT_RATE_MIN
    input.max = VALIDATION_CONSTRAINTS.DEFAULT_RATE_MAX
    input.required = true

    rateWrap.appendChild(prefix)
    rateWrap.appendChild(input)
    tdRate.appendChild(rateWrap)

    // Third cell: remove button
    const tdRemove = document.createElement('td')
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
    tdRemove.appendChild(spanCenter)

    tr.appendChild(tdProfile)
    tr.appendChild(tdRate)
    tr.appendChild(tdRemove)

    return tr
}
