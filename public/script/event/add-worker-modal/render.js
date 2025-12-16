import { createFullName } from '../../utility/utility.js'

const ICON_PATH = '/public/asset/image/icon/'

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
