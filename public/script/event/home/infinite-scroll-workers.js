import { infiniteScroll } from '../../utility/infinite-scroll.js'
import { Http } from '../../utility/http.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { createWorkerListCard } from './create-worker-list-card.js'
import { Dialog } from '../../render/dialog.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const projectContainer = document.querySelector('.project-container')

const projectId = projectContainer?.dataset.projectid
if (!projectId || projectId.trim() === '') {
    console.warn('Project ID not found.')
}

const workerList = projectContainer?.querySelector('.worker-list > .list')
if (!workerList) {
    console.warn('Worker List element not found.')
}

const sentinel = projectContainer?.querySelector('.sentinel')
if (!sentinel) {
    console.warn('Sentinel element not found.')
}

try {
    // Initialize infinite scroll for loading workers
    infiniteScroll(
        workerList,
        sentinel,
        (offset) => asyncFunction(offset), // Fetch data from the server
        (worker) => createWorkerListCard(worker), // Render data into the DOM
        getExistingItemsCount() // Get the count of existing items for offet 
    )
} catch (error) {
    handleException(error, 'Error initializing infinite scroll:', error)
}

/**
 * Retrieves the current count of existing worker items displayed or specified by the URL.
 *
 * This function checks the current page's URL for an 'offset' query parameter. If present,
 * it returns its value as the count of existing items. If not, it falls back to counting
 * the number of elements with the class 'worker-list-card' within the 'workerList' container.
 *
 * @returns {number|string} The number of existing worker items, either from the 'offset' query parameter (string)
 *                          or by counting '.worker-list-card' elements (number).
 */
function getExistingItemsCount() {
    const queryParams = new URLSearchParams(window.location.search)
    const fromQueryParams = queryParams.get('offset')
    const fromDOM = workerList.querySelectorAll('.user-list-card').length

    return Math.max(fromQueryParams ? parseInt(fromQueryParams, 10) : 0, fromDOM)
}

/**
 * Fetches a list of assigned workers for a given project with infinite scroll support.
 *
 * This asynchronous function performs the following:
 * - Prevents concurrent requests using the isLoading flag.
 * - Validates the offset parameter to ensure it is a non-negative number.
 * - Checks for a valid projectId before making the request.
 * - Constructs query parameters to filter workers by status and exclude terminated projects.
 * - Sends a GET request to the appropriate endpoint using the Http.GET utility.
 * - Returns the response data containing the list of workers.
 * - Handles and propagates errors encountered during the process.
 * - Ensures the isLoading flag is reset after the request completes.
 *
 * @param {number} offset The offset for pagination; must be a non-negative integer.
 * @throws {Error} If the offset is invalid, projectId is missing, or the request fails.
 * @returns {Promise<Object>} A promise that resolves to the response data containing workers.
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

        // Construct query parameters
        const queryParams = new URLSearchParams()
        queryParams.append('status', 'assigned')
        queryParams.append('excludeProjectTerminated', 'true')
        queryParams.append('offset', offset)

        const endpoint = `projects/${projectId}/workers?${queryParams.toString()}`
        const response = await Http.GET(endpoint)
        if (!response?.data) {
            throw error
        }

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}