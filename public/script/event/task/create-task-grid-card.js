import { formatDate } from '../../utility/utility.js'

const taskGridContainer = document.querySelector('.task-grid-container')
const projectId = taskGridContainer?.dataset.projectid
if (!projectId || projectId.trim() === '')
    console.warn('Project ID not found.')

/**
 * Creates a task grid card DOM element
 * @param {Object} task - The task data object
 * @param {string} projectId - The project ID
 * @returns {HTMLElement} The task grid card element
 */
export function createTaskGridCard(task) {
    // Create main card container
    const taskCard = document.createElement('div')
    taskCard.className = 'task-grid-card'

    // Create link wrapper
    const link = document.createElement('a')
    link.className = 'flex-col full-body-content'
    link.href = `project/${projectId}/task/${task.id}`

    // Create task header section
    const headerSection = document.createElement('section')

    const textWithIcon = document.createElement('div')
    textWithIcon.className = 'text-w-icon'

    const taskIcon = document.createElement('img')
    taskIcon.src = 'asset/image/icon/task_w.svg'
    taskIcon.alt = 'Task'
    taskIcon.title = 'Task'
    taskIcon.height = 24

    const taskName = document.createElement('h3')
    taskName.className = 'task-name'
    taskName.textContent = task.name

    textWithIcon.appendChild(taskIcon)
    textWithIcon.appendChild(taskName)

    const taskIdPara = document.createElement('p')
    taskIdPara.className = 'task-id'
    const taskIdEm = document.createElement('em')
    taskIdEm.textContent = task.id
    taskIdPara.appendChild(taskIdEm)

    headerSection.appendChild(textWithIcon)
    headerSection.appendChild(taskIdPara)

    // Create task description
    const description = document.createElement('p')
    description.className = 'task-description multi-line-ellipsis'
    description.title = task.description
    description.textContent = task.description

    // Create task schedule section
    const scheduleSection = document.createElement('section')
    scheduleSection.className = 'task-schedule flex-col'

    // Start date
    const startDateRow = document.createElement('div')
    startDateRow.className = 'flex-row'

    const startTextWithIcon = document.createElement('div')
    startTextWithIcon.className = 'text-w-icon'

    const startIcon = document.createElement('img')
    startIcon.src = 'asset/image/icon/start_w.svg'
    startIcon.alt = 'Start Date'
    startIcon.title = 'Start Date'
    startIcon.height = 20

    const startLabel = document.createElement('p')
    startLabel.textContent = 'Start: '

    startTextWithIcon.appendChild(startIcon)
    startTextWithIcon.appendChild(startLabel)

    const startDate = document.createElement('p')
    const startDateStrong = document.createElement('strong')
    startDateStrong.textContent = formatDate(task.startDateTime)
    startDate.appendChild(startDateStrong)

    startDateRow.appendChild(startTextWithIcon)
    startDateRow.appendChild(startDate)

    // Completion date
    const completionDateRow = document.createElement('div')
    completionDateRow.className = 'flex-row'

    const completionTextWithIcon = document.createElement('div')
    completionTextWithIcon.className = 'text-w-icon'

    const completionIcon = document.createElement('img')
    completionIcon.src = 'asset/image/icon/complete_w.svg'
    completionIcon.alt = 'Completion Date'
    completionIcon.title = 'Completion Date'
    completionIcon.height = 20

    const completionLabel = document.createElement('p')
    completionLabel.textContent = 'End: '

    completionTextWithIcon.appendChild(completionIcon)
    completionTextWithIcon.appendChild(completionLabel)

    const completionDate = document.createElement('p')
    const completionDateStrong = document.createElement('strong')
    completionDateStrong.textContent = formatDate(task.completionDateTime)
    completionDate.appendChild(completionDateStrong)

    completionDateRow.appendChild(completionTextWithIcon)
    completionDateRow.appendChild(completionDate)

    scheduleSection.appendChild(startDateRow)
    scheduleSection.appendChild(completionDateRow)

    // Create badges section
    const badgeSection = document.createElement('section')
    badgeSection.className = 'task-badge flex-row flex-child-end-h'

    // Priority badge
    const priorityBadge = createPriorityBadge(task.priority)
    badgeSection.appendChild(priorityBadge)

    // Status badge
    const statusBadge = createStatusBadge(task.status)
    badgeSection.appendChild(statusBadge)

    // Assemble the card
    link.appendChild(headerSection)
    link.appendChild(description)
    link.appendChild(scheduleSection)
    link.appendChild(badgeSection)

    taskCard.appendChild(link)

    taskGridContainer.appendChild(taskCard)
}

/**
 * Creates a priority badge element
 * @param {string} priority - The task priority ('low', 'medium', 'high')
 * @returns {HTMLElement} The priority badge element
 */
function createPriorityBadge(priority) {
    const badge = document.createElement('div')
    badge.className = 'priority-badge badge center-child'

    const priorityText = document.createElement('p')
    priorityText.className = 'center-text'

    switch (priority.toLowerCase()) {
        case 'low':
            badge.classList.add('green-bg')
            priorityText.classList.add('white-text')
            priorityText.textContent = 'Low'
            break
        case 'medium':
            badge.classList.add('yellow-bg')
            priorityText.classList.add('black-text')
            priorityText.textContent = 'Medium'
            break
        case 'high':
            badge.classList.add('red-bg')
            priorityText.classList.add('white-text')
            priorityText.textContent = 'High'
            break
        default:
            badge.classList.add('yellow-bg')
            priorityText.classList.add('black-text')
            priorityText.textContent = 'Medium'
    }
    badge.classList.add('status-badge', 'badge')

    badge.appendChild(priorityText)
    return badge
}

/**
 * Creates a status badge element
 * @param {string} status - The work status ('pending', 'onGoing', 'completed', 'delayed', 'cancelled')
 * @returns {HTMLElement} The status badge element
 */
function createStatusBadge(status) {
    const badge = document.createElement('div')
    badge.className = 'status-badge center-child'

    const statusText = document.createElement('p')
    statusText.className = 'center-text'

    switch (status.toLowerCase()) {
        case 'pending':
            badge.classList.add('yellow-bg')
            statusText.classList.add('black-text')
            statusText.textContent = 'Pending'
            break
        case 'ongoing':
        case 'onGoing':
            badge.classList.add('green-bg')
            statusText.classList.add('white-text')
            statusText.textContent = 'On Going'
            break
        case 'completed':
            badge.classList.add('blue-bg')
            statusText.classList.add('white-text')
            statusText.textContent = 'Completed'
            break
        case 'delayed':
            badge.classList.add('orange-bg')
            statusText.classList.add('white-text')
            statusText.textContent = 'Delayed'
            break
        case 'cancelled':
            badge.classList.add('red-bg')
            statusText.classList.add('white-text')
            statusText.textContent = 'Cancelled'
            break
        default:
            badge.classList.add('yellow-bg')
            statusText.classList.add('black-text')
            statusText.textContent = 'Pending'
    }
    badge.classList.add('status-badge', 'badge')

    badge.appendChild(statusText)
    return badge
}

