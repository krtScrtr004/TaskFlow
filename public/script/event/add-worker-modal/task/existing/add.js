import { addWorker } from '../../modal.js'
import { Http } from '../../../../utility/http.js'
import { Dialog } from '../../../../render/dialog.js'
import { Notification } from '../../../../render/notification.js'

let isLoading = false
const viewTaskInfo = document.querySelector('.view-task-info')
const thisProjectId = viewTaskInfo?.dataset.projectid
if (!thisProjectId || thisProjectId.trim() === '') {
    console.error('Project ID not found.')
    Dialog.somethingWentWrong()
}

// Just add workers with default behavior
await addWorker(
    thisProjectId,
    async (projectId, workerIds) => await sendToBackend(projectId, workerIds),
    () => { },
    () => {
        const delay = 1500
        Notification.success('Workers added to task successfully.', delay)
        setTimeout(() => window.location.reload(), delay)
    }
)

async function sendToBackend(projectId, workerIds) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID is required.')
        }

        const taskId = viewTaskInfo?.dataset.taskid
        if (!taskId || taskId.trim() === '') {
            throw new Error('Task ID not found in the DOM.')
        }

        if (!workerIds || workerIds.length === 0) {
            throw new Error('No worker IDs provided.')
        }

        const response = await Http.POST(`projects/${projectId}/tasks/${taskId}/workers`, { workerIds })
        if (!response) {
            throw new Error('No response from server.')
        }

        return response
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}

/**
 * Creates a worker grid card element matching the PHP workerGridCard function
 * @param {Object} workerData - The worker data object
 * @param {string} workerData.id - Worker public ID
 * @param {string} workerData.firstName - Worker first name
 * @param {string} workerData.lastName - Worker last name
 * @param {string} workerData.email - Worker email
 * @param {string} workerData.contactNumber - Worker contact number
 * @param {string} [workerData.profileLink] - Optional profile image URL
 * @param {string} workerData.status - Worker status
 * @param {number} [workerData.completedTasks=0] - Number of completed tasks
 * @param {number} [workerData.performance=0] - Worker performance percentage (0-100)
 * @returns {HTMLElement} The rendered worker grid card element
 */
function createWorkerGridCard(workerData) {
    const {
        id,
        firstName,
        lastName,
        email,
        contactNumber,
        profileLink,
        status,
        completedTasks = 0,
        performance = 0
    } = workerData

    const fullName = `${firstName} ${lastName}`
    const profileSrc = profileLink || 'asset/image/icon/profile_w.svg'

    // Create main button container
    const button = document.createElement('button')
    button.className = 'worker-grid-card unset-button'
    button.dataset.workerid = id

    // Create worker primary info section
    const primaryInfo = createWorkerPrimaryInfo(fullName, id, profileSrc)

    // Create statistics section
    const statistics = createWorkerStatistics(completedTasks, performance)

    // Create horizontal rule
    const hr = document.createElement('hr')

    // Create contact info section
    const contactInfo = createWorkerContactInfo(email, contactNumber)

    // Create status section
    const statusSection = createWorkerStatus(status)

    // Assemble the card
    button.appendChild(primaryInfo)
    button.appendChild(statistics)
    button.appendChild(hr)
    button.appendChild(contactInfo)
    button.appendChild(statusSection)

    return button
}

/**
 * Creates the worker primary info section
 * @param {string} name - Worker full name
 * @param {string} id - Worker ID
 * @param {string} profileSrc - Profile image source
 * @returns {HTMLElement} Primary info section element
 */
function createWorkerPrimaryInfo(name, id, profileSrc) {
    const section = document.createElement('section')
    section.className = 'worker-primary-info flex-row flex-child-center-h'

    // Create profile picture
    const profileImg = document.createElement('img')
    profileImg.className = 'circle fit-contain'
    profileImg.src = profileSrc
    profileImg.alt = name
    profileImg.title = name
    profileImg.height = 32

    // Create info container
    const infoDiv = document.createElement('div')
    infoDiv.className = 'flex-col'

    // Create worker name
    const workerName = document.createElement('h3')
    workerName.className = 'worker-name start-text'
    workerName.textContent = name

    // Create worker ID
    const workerId = document.createElement('p')
    workerId.className = 'worker-id start-text'
    workerId.innerHTML = `<em>${id}</em>`

    // Assemble info container
    infoDiv.appendChild(workerName)
    infoDiv.appendChild(workerId)

    // Assemble primary info
    section.appendChild(profileImg)
    section.appendChild(infoDiv)

    return section
}

/**
 * Creates the worker statistics section
 * @param {number} completedTasks - Number of completed tasks
 * @param {number} performance - Performance percentage
 * @returns {HTMLElement} Statistics section element
 */
function createWorkerStatistics(completedTasks, performance) {
    const section = document.createElement('section')
    section.className = 'worker-statistics flex-col'

    // Create completed tasks paragraph
    const tasksP = document.createElement('p')
    tasksP.textContent = `Completed Tasks: ${completedTasks}`

    // Create performance paragraph
    const performanceP = document.createElement('p')
    performanceP.textContent = `Performance: ${performance}%`

    section.appendChild(tasksP)
    section.appendChild(performanceP)

    return section
}

/**
 * Creates the worker contact info section
 * @param {string} email - Worker email
 * @param {string} contactNumber - Worker contact number
 * @returns {HTMLElement} Contact info section element
 */
function createWorkerContactInfo(email, contactNumber) {
    const section = document.createElement('section')
    section.className = 'worker-contact-info flex-col'

    // Create email div
    const emailDiv = createContactItem(
        'asset/image/icon/email_w.svg',
        'Worker Email',
        `Email: ${email}`
    )

    // Create contact number div
    const contactDiv = createContactItem(
        'asset/image/icon/contact_w.svg',
        'Contact Number',
        `Contact: ${contactNumber}`
    )

    section.appendChild(emailDiv)
    section.appendChild(contactDiv)

    return section
}

/**
 * Creates a contact item with icon and text
 * @param {string} iconSrc - Icon source path
 * @param {string} iconAlt - Icon alt text
 * @param {string} text - Contact text
 * @returns {HTMLElement} Contact item element
 */
function createContactItem(iconSrc, iconAlt, text) {
    const div = document.createElement('div')
    div.className = 'text-w-icon'

    const icon = document.createElement('img')
    icon.src = iconSrc
    icon.alt = iconAlt
    icon.title = iconAlt
    icon.height = 20

    const p = document.createElement('p')
    p.textContent = text

    div.appendChild(icon)
    div.appendChild(p)

    return div
}

/**
 * Creates the worker status section
 * @param {string} status - Worker status
 * @returns {HTMLElement} Status section element
 */
function createWorkerStatus(status) {
    const section = document.createElement('section')
    section.className = 'worker-status flex-col flex-child-end-h flex-child-end-v'

    const statusDiv = document.createElement('div')

    // Create status badge based on status value
    const badge = createStatusBadge(status)
    statusDiv.appendChild(badge)

    section.appendChild(statusDiv)

    return section
}

/**
 * Creates a status badge element
 * @param {string} status - Worker status (ACTIVE, INACTIVE, TERMINATED)
 * @returns {HTMLElement} Status badge element
 */
function createStatusBadge(status) {
    const badge = document.createElement('div')
    badge.className = 'status-badge center-child'

    const p = document.createElement('p')
    p.className = 'center-text'

    // Map status to display text and styling
    const statusMap = {
        'ACTIVE': { text: 'Active', bgClass: 'green-bg', textClass: 'white-text' },
        'INACTIVE': { text: 'Inactive', bgClass: 'yellow-bg', textClass: 'black-text' },
        'TERMINATED': { text: 'Terminated', bgClass: 'red-bg', textClass: 'white-text' }
    }

    const statusConfig = statusMap[status.toUpperCase()] || statusMap['INACTIVE']

    badge.classList.add(statusConfig.bgClass)
    p.classList.add(statusConfig.textClass)
    p.textContent = statusConfig.text

    badge.appendChild(p)

    return badge
}

/**
 * Renders multiple worker grid cards and appends them to a container
 * @param {Array} workersData - Array of worker data objects
 * @param {HTMLElement} container - Container element to append cards to
 * @param {boolean} [clearContainer=true] - Whether to clear container before adding cards
 */
export function renderWorkerGridCards(workersData, container, clearContainer = true) {
    if (clearContainer) {
        container.innerHTML = ''
    }

    workersData.forEach(workerData => {
        const workerCard = createWorkerGridCard(workerData)
        container.appendChild(workerCard)
    })
}
