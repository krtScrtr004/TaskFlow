import { infiniteScroll } from '../../utility/infinite-scroll.js'
import { Http } from '../../utility/http.js'
import { createTaskGridCard } from './create-task-grid-card.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const taskGridContainer = document.querySelector('.task-grid-container')
if (!taskGridContainer) {
    console.warn('Task Grid Container element not found.')
}

const sentinel = taskGridContainer?.querySelector('.sentinel')
if (!sentinel) {
    console.warn('Sentinel element not found.')
}
const projectId = taskGridContainer?.dataset.projectid
if (!projectId || projectId.trim() === '') {
    console.warn('Project ID not found.')
}

const taskGrid = taskGridContainer?.querySelector('.task-grid')
try {
    // Initialize infinite scroll for loading tasks
    infiniteScroll(
        taskGrid,
        sentinel,
        (offset) => asyncFunction(offset), // Fetch function
        (task) => createTaskGridCard(task), // DOM creation function
        getExistingItemsCount()
    )
} catch (error) {
    handleException(error, 'Error initializing infinite scroll:', error)
}

/**
 * Returns the number of existing task items currently displayed or specified in the URL.
 *
 * This function checks the current URL for an 'offset' query parameter. If present, it returns its value.
 * If not, it counts the number of task cards in the task grid, excluding elements with the 'add-task-button' class.
 *
 * @returns {number|string} The number of existing task items, either from the 'offset' query parameter (as a string) or as a count of DOM elements (number).
 */
function getExistingItemsCount() {
    const queryParams = new URLSearchParams(window.location.search)
    const fromQueryParams = queryParams.get('offset')
    const fromDOM = taskGrid.querySelectorAll('.task-grid-card:not(.add-task-button)').length

    return Math.max(fromQueryParams ? parseInt(fromQueryParams, 10) : 0, fromDOM) ?? 0
}

/**
 * Fetches a list of tasks for a specific project using infinite scroll pagination.
 *
 * This asynchronous function performs the following:
 * - Checks if a request is already in progress to prevent duplicate calls.
 * - Validates the provided offset value to ensure it is a non-negative number.
 * - Validates the presence of a valid projectId.
 * - Sends a GET request to fetch tasks for the given project and offset.
 * - Handles errors and ensures the loading state is properly managed.
 *
 * @param {number} offset The number of tasks to skip before starting to collect the result set. Must be a non-negative integer.
 * @throws {Error} If the offset is invalid, projectId is missing, or the server response is invalid.
 * @returns {Promise<any>} A promise that resolves to the response data containing the list of tasks.
 */
async function asyncFunction(offset) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (isNaN(offset) || offset < 0) {
            throw new Error('Invalid offset value.')
        }

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID not found.')
        }

        const currentUrl = window.location.href
        const subpath = currentUrl.split('TaskFlow/')[1]

        // Build endpoint path from page URL 
        let endpointPath = ''
        endpointPath = subpath.replace('project', 'projects')
        endpointPath = endpointPath.replace('task', 'tasks')
        if (subpath.includes('phase')) {
            endpointPath = endpointPath.replace('phase', 'phases')
        }
        if (subpath.includes('worker')) {
            endpointPath = endpointPath.replace('worker', 'workers')
        }

        const queryParams = new URLSearchParams(window.location.search)
        queryParams.set('offset', offset)

        const response = await Http.GET(`${endpointPath}?${queryParams.toString()}`)
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