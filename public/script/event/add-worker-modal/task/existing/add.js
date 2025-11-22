import { addWorker } from '../../modal.js'
import { Http } from '../../../../utility/http.js'
import { Dialog } from '../../../../render/dialog.js'
import { Notification } from '../../../../render/notification.js'
import { createFullName } from '../../../../utility/utility.js'

let isLoading = false

const viewTaskInfo = document.querySelector('.view-task-info')

const thisProjectId = viewTaskInfo?.dataset.projectid
if (!thisProjectId || thisProjectId.trim() === '') {
    console.error('Project ID not found.')
    Dialog.somethingWentWrong()
}

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

/**
 * Sends a request to the backend to add workers to a specific task within a project phase.
 *
 * This function performs several validation checks before making the request:
 * - Ensures no concurrent requests are in progress
 * - Validates that projectId and taskId are present and non-empty
 * - Validates that workerIds is a non-empty array
 * - Throws descriptive errors for missing or invalid parameters
 * - Sends a POST request to the backend with the provided worker IDs
 *
 * @param {string} projectId - The unique identifier of the project.
 * @param {string[]} workerIds - Array of worker IDs to be added to the task.
 * 
 * @throws {Error} If projectId is missing or empty.
 * @throws {Error} If taskId is missing or empty in the DOM.
 * @throws {Error} If workerIds is missing or empty.
 * @throws {Error} If no response is received from the server.
 * @throws {Error} For any other errors encountered during the request.
 * 
 * @returns {Promise<Object>} The response object from the backend if successful.
 */
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

        const phaseId = viewTaskInfo?.dataset.phaseid
        if (!phaseId || phaseId.trim() === '') {
            throw new Error('Phase ID not found in the DOM.')
        }

        const taskId = viewTaskInfo?.dataset.taskid
        if (!taskId || taskId.trim() === '') {
            throw new Error('Task ID not found in the DOM.')
        }

        if (!workerIds || workerIds.length === 0) {
            throw new Error('No worker IDs provided.')
        }

        const response = await Http.POST(`projects/${projectId}/phases/${phaseId}/tasks/${taskId}/workers`, { workerIds })
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
 * Creates a worker grid card element for displaying worker information.
 *
 * This function generates a button element representing a worker, including:
 * - Primary info (name, profile image, ID)
 * - Statistics (completed tasks, performance)
 * - Contact info (email, contact number)
 * - Status section
 * - Horizontal rule for separation
 *
 * @param {Object} workerData Object containing worker data with the following properties:
 *      - id {string|number} Unique worker identifier
 *      - firstName {string} Worker's first name
 *      - middleName {string} (optional) Worker's middle name
 *      - lastName {string} Worker's last name
 *      - email {string} Worker's email address
 *      - contactNumber {string} Worker's contact number
 *      - profileLink {string} (optional) URL to worker's profile image
 *      - status {string} Worker's current status
 *      - completedTasks {number} (optional) Number of completed tasks (default: 0)
 *      - performance {number} (optional) Performance score (default: 0)
 *
 * @returns {HTMLButtonElement} Button element representing the worker grid card
 */
function createWorkerGridCard(workerData) {
    const {
        id,
        firstName,
        middleName,
        lastName,
        email,
        contactNumber,
        profileLink,
        status,
        completedTasks = 0,
        performance = 0
    } = workerData

    const fullName = createFullName(firstName, middleName, lastName)
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
