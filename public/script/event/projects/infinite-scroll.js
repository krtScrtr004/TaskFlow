import { infiniteScroll } from '../../utility/infinite-scroll.js'
import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { formatDate } from '../../utility/utility.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const projectGridContainer = document.querySelector('.project-grid-container')

const projectGrid = projectGridContainer?.querySelector('.project-grid')
if (!projectGrid) {
    console.warn('Project Grid element not found.')
}

const sentinel = projectGridContainer?.querySelector('.sentinel')
if (!sentinel) {
    console.warn('Sentinel element not found.')
}

try {
    // Initialize infinite scroll for loading projects
    infiniteScroll(
        projectGrid,
        sentinel,
        (offset) => asyncFunction(offset),
        (project) => domCreator(project),
        getExistingItemsCount()
    )
} catch (error) {
    handleException(error, 'Error initializing infinite scroll:', error)
}

/**
 * Retrieves the current count of existing project items displayed or specified by the URL.
 *
 * This function checks the current page's URL for an 'offset' query parameter.
 * - If 'offset' is present, its value is returned as the count of existing items.
 * - If 'offset' is not present, the function counts the number of elements with the
 *   class 'project-grid-card' within the 'projectGrid' container and returns that count.
 *
 * @returns {number|string} The number of existing project items, either from the 'offset'
 *   query parameter (as a string) or by counting '.project-grid-card' elements (as a number).
 */
function getExistingItemsCount() {
    const queryParams = new URLSearchParams(window.location.search)
    const fromQueryParams = queryParams.get('offset')
    const fromDOM = projectGrid.querySelectorAll('.project-grid-card').length

    return Math.max(fromQueryParams ? parseInt(fromQueryParams, 10) : 0, fromDOM)
}

/**
 * Fetches project data asynchronously from the server using an offset for pagination.
 *
 * This function manages loading state to prevent concurrent requests and validates the offset parameter:
 * - Throws an error if the offset is missing, not a number, or negative.
 * - Sends a GET request to the 'projects' endpoint with the specified offset.
 * - Throws an error if the server response is missing.
 * - Returns the data property from the server response.
 * - Ensures the loading state is reset after the request completes.
 *
 * @param {number} offset The offset value for pagination; must be a non-negative number.
 * @returns {Promise<any>} A promise that resolves to the project data returned by the server.
 * @throws {Error} If the offset is invalid, if a request is already in progress, or if the server response is missing.
 */
async function asyncFunction(offset) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!offset || isNaN(offset) || offset < 0) {
            throw new Error('Invalid offset value.')
        }

        const queryParams = new URLSearchParams(window.location.search)
        queryParams.set('offset', offset)

        const response = await Http.GET(`projects?${queryParams.toString()}`)
        if (!response) {
            throw new Error('No response from server.')
        }

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}

/**
 * Creates a project grid card DOM element
 * @param {Object} project - The project data object
 * @returns {HTMLElement} The project grid card element
 */
function domCreator(project) {
    const ICON_PATH = 'asset/image/icon/'
    const REDIRECT_PATH = '/TaskFlow/home/' 
    const id = project.id
    const name = project.name
    const description = project.description || 'No description provided'
    const startDateTime = formatDate(project.startDateTime)
    const completionDateTime = formatDate(project.completionDateTime)
    const status = project.status

    // Create main card container
    const card = document.createElement('div')
    card.className = 'project-grid-card'

    // Create link wrapper
    const link = document.createElement('a')
    link.className = 'full-body-content flex-col'
    link.href = `${REDIRECT_PATH}${id}`

    // Create primary info section
    const primaryInfoSection = document.createElement('section')
    primaryInfoSection.className = 'project-primary-info'

    // Create name section with icon
    const nameDiv = document.createElement('div')
    nameDiv.className = 'text-w-icon'

    const projectIcon = document.createElement('img')
    projectIcon.src = ICON_PATH + 'project_w.svg'
    projectIcon.alt = 'Project Name'
    projectIcon.title = 'Project Name'
    projectIcon.height = 24

    const nameHeading = document.createElement('h3')
    nameHeading.className = 'project-name single-line-ellipsis'
    nameHeading.title = name
    nameHeading.textContent = name

    nameDiv.appendChild(projectIcon)
    nameDiv.appendChild(nameHeading)

    // Create schedule section
    const scheduleDiv = document.createElement('div')
    scheduleDiv.className = 'project-schedule flex-row'
    scheduleDiv.textContent = `${startDateTime} - ${completionDateTime}`

    primaryInfoSection.appendChild(nameDiv)
    primaryInfoSection.appendChild(scheduleDiv)

    // Create description paragraph
    const descriptionP = document.createElement('p')
    descriptionP.className = 'project-description multi-line-ellipsis-7'
    descriptionP.title = description
    descriptionP.textContent = description

    // Create status section
    const statusDiv = document.createElement('div')
    statusDiv.className = 'project-status flex-col flex-child-end-v'

    const statusBadge = createStatusBadge(status)
    statusDiv.appendChild(statusBadge)

    // Assemble the card
    link.appendChild(primaryInfoSection)
    link.appendChild(descriptionP)
    link.appendChild(statusDiv)

    card.appendChild(link)

    projectGrid.appendChild(card)
}

/**
 * Creates a status badge element
 * @param {string} status - The work status ('pending', 'onGoing', 'completed', 'delayed', 'cancelled')
 * @returns {HTMLElement} The status badge element
 */
function createStatusBadge(status) {
    const badge = document.createElement('div')
    badge.className = 'status-badge badge center-child'

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

    badge.appendChild(statusText)
    return badge
}