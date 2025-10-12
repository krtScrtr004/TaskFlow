import { addWorker } from '../../shared.js'
import { Dialog } from '../../../../render/dialog.js'
import { Http } from '../../../../utility/http.js'

let isLoading = false
export const assignedWorkers = {}
const addTaskForm = document.querySelector('#add_task_form')
const noAssignedWorkerWall = addTaskForm?.querySelector('.no-assigned-worker-wall')
const thisProjectId = addTaskForm?.dataset.projectid
if (!thisProjectId || thisProjectId.trim() === '') {
    console.error('Project ID not found.')
    Dialog.somethingWentWrong()
}

try {
    await addWorker(
        thisProjectId,
        async (projectId, workersId) => sendToBackend(projectId, workersId),
        (workersData) => action(workersData),
        () => {
            Dialog.operationSuccess('Workers Added.', 'The selected workers have been added to the task.')
        }
    )

    const taskWorkerList = addTaskForm.querySelector('.task-worker > .list')
    if (taskWorkerList) {
        taskWorkerList.addEventListener('click', e => {
            e.preventDefault()

            const removeWorkerButton = e.target.closest('#remove_worker_button')
            if (!removeWorkerButton) return

            const workerCard = removeWorkerButton.closest('.task-worker-card')
            if (!workerCard)
                throw new Error('Worker card element not found.')

            delete assignedWorkers[workerCard.dataset.id]
            workerCard.remove()
            if (Object.keys(assignedWorkers).length === 0 && noAssignedWorkerWall) {
                noAssignedWorkerWall.classList.remove('no-display')
                noAssignedWorkerWall.classList.add('flex-col')
            }
        })
    }
} catch (error) {
    console.error('Error adding worker:', error)
    Dialog.somethingWentWrong()
}

/**
 * Renders a task worker card element
 * @param {Object} workerData - The worker data object
 * @param {string} workerData.id - Worker ID
 * @param {string} workerData.name - Worker name
 * @param {Array} workerData.jobTitles - Array of job title strings
 * @param {number} workerData.performance - Worker performance percentage (0-100)
 * @param {number} workerData.completedTasks - Number of completed tasks
 * @param {string} [workerData.profileImage] - Optional profile image URL
 * @returns {HTMLElement} The rendered task worker card element
 */
function createTaskWorkerCard(workerData) {
    const {
        id,
        name,
        jobTitles = [],
        performance = 0,
        completedTasks = 0,
        profileImage
    } = workerData

    // Create main container
    const workerCard = document.createElement('div')
    workerCard.className = 'task-worker-card flex-col'
    workerCard.dataset.id = id

    // Create worker primary info section
    const primaryInfo = createWorkerPrimaryInfo(name, id, profileImage)

    // Create job titles section
    const jobTitlesSection = createJobTitlesSection(jobTitles)

    // Create statistics section
    const statisticsSection = createWorkerStatistics(performance, completedTasks)

    // Assemble the card
    workerCard.appendChild(primaryInfo)
    workerCard.appendChild(jobTitlesSection)
    workerCard.appendChild(statisticsSection)

    return workerCard
}

/**
 * Creates the worker primary info section
 * @param {string} name - Worker name
 * @param {string} workerId - Worker ID
 * @param {string} [profileImage] - Optional profile image URL
 * @returns {HTMLElement} Primary info section element
 */
function createWorkerPrimaryInfo(name, workerId, profileImage) {
    const section = document.createElement('section')
    section.className = 'worker-primary-info flex-row flex-child-center-h'

    // Create worker icon/image
    const workerIcon = document.createElement('img')
    if (profileImage) {
        workerIcon.src = profileImage
        workerIcon.className = 'worker-profile-image'
    } else {
        workerIcon.src = '/TaskFlow/public/asset/image/icon/worker_w.svg'
    }
    workerIcon.alt = name
    workerIcon.title = name
    workerIcon.height = 30

    // Create worker info container
    const infoContainer = document.createElement('section')
    infoContainer.className = 'flex-col'

    // Create worker name and remove button container
    const nameContainer = document.createElement('div')
    nameContainer.className = 'flex-row flex-space-between'

    // Create worker name
    const workerName = document.createElement('h3')
    workerName.className = 'worker-name'
    workerName.textContent = name

    // Create remove worker button
    const removeButton = document.createElement('button')
    removeButton.id = 'remove_worker_button'
    removeButton.type = 'button'
    removeButton.className = 'unset-button'

    const removeIcon = document.createElement('img')
    removeIcon.src = '/TaskFlow/public/asset/image/icon/delete_r.svg'
    removeIcon.alt = 'Remove Worker'
    removeIcon.title = 'Remove Worker'
    removeIcon.height = 18

    removeButton.appendChild(removeIcon)

    // Assemble name container
    nameContainer.appendChild(workerName)
    nameContainer.appendChild(removeButton)

    // Create worker ID
    const workerIdElement = document.createElement('p')
    workerIdElement.className = 'worker-id'
    workerIdElement.innerHTML = `<em>${workerId}</em>`

    // Assemble info container
    infoContainer.appendChild(nameContainer)
    infoContainer.appendChild(workerIdElement)

    section.appendChild(workerIcon)
    section.appendChild(infoContainer)

    return section
}

/**
 * Creates the job titles section
 * @param {Array} jobTitles - Array of job title strings
 * @returns {HTMLElement} Job titles section element
 */
function createJobTitlesSection(jobTitles) {
    const section = document.createElement('section')
    section.className = 'worker-job-titles flex-row flex-wrap'

    jobTitles.forEach(title => {
        const titleElement = document.createElement('span')
        titleElement.className = 'job-title-chip'
        titleElement.textContent = title
        section.appendChild(titleElement)
    })

    return section
}

/**
 * Creates the worker statistics section
 * @param {number} performance - Performance percentage
 * @param {number} completedTasks - Number of completed tasks
 * @returns {HTMLElement} Statistics section element
 */
function createWorkerStatistics(performance, completedTasks) {
    const section = document.createElement('section')
    section.className = 'worker-statistics flex-col'

    // Create performance statistic
    const performanceDiv = createStatisticItem(
        '/TaskFlow/public/asset/image/icon/progress_w.svg',
        'Worker Performance',
        `Performance: ${performance}%`
    )

    // Create completed tasks statistic
    const tasksDiv = createStatisticItem(
        '/TaskFlow/public/asset/image/icon/task_w.svg',
        'Worker Completed Task',
        `Completed Tasks: ${completedTasks}`
    )

    section.appendChild(performanceDiv)
    section.appendChild(tasksDiv)

    return section
}

/**
 * Creates a statistic item with icon and text
 * @param {string} iconSrc - Icon source path
 * @param {string} iconAlt - Icon alt text
 * @param {string} text - Statistic text
 * @returns {HTMLElement} Statistic item element
 */
function createStatisticItem(iconSrc, iconAlt, text) {
    const div = document.createElement('div')
    div.className = 'text-w-icon'

    const icon = document.createElement('img')
    icon.className = 'fit-contain'
    icon.src = iconSrc
    icon.alt = iconAlt
    icon.title = iconAlt
    icon.height = 16

    const textElement = document.createElement('p')
    textElement.textContent = text

    div.appendChild(icon)
    div.appendChild(textElement)

    return div
}

/**
 * 
 * @param {string} projectId - Project ID to see if selected workers are part of the project
 * @param {string[]} workerIds - Array of worker IDs to add to the project
 * @returns {Promise<Object[]>} Resolves with array of worker data objects on success
 */
async function sendToBackend(projectId, workerIds) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID is required.')

        if (!workerIds || workerIds.length === 0)
            throw new Error('No worker IDs provided.')

        const idParams = workerIds.map(id => `${id}`).join(',')
        const response = await Http.GET(`projects/${projectId}/workers?ids=${idParams}`)
        if (!response) 
            throw new Error('No response from server.')
        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}

function action(workersData) {
    workersData.forEach(workerData => {
        const taskWorkerCard = createTaskWorkerCard(workerData)
        const taskWorkerList = document.querySelector('#add_task_form .task-worker > .list')
        if (!taskWorkerList) throw new Error('Task worker list container not found.')

        taskWorkerList.appendChild(taskWorkerCard)
        noAssignedWorkerWall?.classList.add('no-display')
        noAssignedWorkerWall?.classList.remove('flex-col')
        assignedWorkers[workerData.id] = workerData
    })
}

