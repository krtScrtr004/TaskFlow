import { Loader } from '../../render/loader.js'
import { Dialog } from '../../render/dialog.js'
import { debounceAsync } from '../../utility/debounce.js'
import { fetchWorkers } from './fetch.js'
import { createWorkerListCard } from './render.js'
import { toggleNoWorkerWall } from './modal.js'
import { infiniteScrollWorkers, disconnectInfiniteScroll } from './infinite-scroll.js'

/**
 * Attaches search event listeners to the worker search form and button within the add worker modal.
 *
 * This function sets up debounced event handlers for submitting the search form and clicking the search button.
 * It validates the provided endpoint, assigns it to a module-level variable, and ensures the required DOM elements exist.
 * When triggered, the event handlers call the asynchronous worker search function with the given project ID.
 *
 * @param {string|number} projectId - The unique identifier of the project to search workers for.
 * @param {string} localEndpoint - The API endpoint to use for searching workers. Must be a non-empty string.
 * @param {Object} options - Optional parameters such as workerListContainer and renderer.
 *
 * @throws {Error} If the provided endpoint is invalid (empty or not provided).
 *
 * @example
 * searchWorkerEvent(123, '/api/workers/search');
 */
export function searchWorkerEvent(projectId, localEndpoint, options = {}) {
    if (!localEndpoint || localEndpoint.trim() === '') {
        throw new Error('Invalid endpoint provided to searchWorkerEvent.')
    }
    
    const container = options.workerListContainer || document.querySelector('#add_worker_modal_template')

    const searchBarForm = container?.querySelector('form.search-bar')
    if (!searchBarForm) {
        console.error('Search bar form not found.')
        return
    }

    const button = searchBarForm.querySelector('#search_assigned_worker_button') || searchBarForm.querySelector('#search_worker_button')
    if (!button) {
        console.error('Search button not found.')
        return
    }

    // Create a closure to capture the current context
    const searchHandler = (e) => debounceAsync(searchForWorker(e, projectId, localEndpoint, container, options), 300)
    
    searchBarForm.addEventListener('submit', searchHandler)
    button.addEventListener('click', searchHandler)

}

/**
 * Handles searching for workers within a project and updates the worker list UI accordingly.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Clears the current worker list and displays a loading indicator.
 * - Validates the provided project ID.
 * - Retrieves the search term from the search bar input.
 * - Fetches workers matching the search term for the given project.
 * - Updates the UI with the search results:
 *   - If workers are found, creates worker list cards and reinitializes infinite scroll.
 *   - If no workers are found, displays a "no workers" message and disconnects infinite scroll.
 * - Handles errors by displaying an error dialog.
 * - Removes the loading indicator when finished.
 *
 * @async
 * @function
 * 
 * @param {Event} e The event object from the search form submission.
 * @param {string} projectId The unique identifier of the project to search workers for.
 * @param {string} endpoint The API endpoint to use for searching workers.
 * @param {HTMLElement} container The container element for the worker list.
 * @param {Object} options Optional parameters.
 * 
 * @returns {Promise<void>} Resolves when the search and UI update process is complete.
 * 
 * @throws {Error} If the project ID is missing or invalid.
 */
async function searchForWorker(e, projectId, endpoint, container, options = {}) {
    e.preventDefault()

    const workerList = container.querySelector('.worker-list > .list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }

    // Disconnect infinite scroll FIRST to prevent race condition
    disconnectInfiniteScroll(container)

    // Clear the worker list
    workerList.textContent = ''

    // Hide no workers message and show worker list
    toggleNoWorkerWall(false, container)

    Loader.full(workerList)

    if (!projectId || projectId.trim() === '') {
        console.error('Project ID is missing.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        const searchTerm = container.parentElement.querySelector('input[type="text"]#search_worker')
            ? container.parentElement.querySelector('input[type="text"]#search_worker').value.trim()
            : container.parentElement.querySelector('input[type="text"]#search_assigned_worker').value.trim()
        
        // Await fetch completion before setting up infinite scroll
        const workers = await fetchWorkers(endpoint, searchTerm, 0)

        if (workers && workers.length > 0) {
            workers.forEach(worker => options.renderer ? options.renderer(worker) : createWorkerListCard(worker))

            // Only NOW setup infinite scroll with fresh state
            infiniteScrollWorkers(projectId, endpoint, searchTerm, options)
        } else {
            // Show no workers message if no results
            toggleNoWorkerWall(true, container)
            // Already disconnected above, no need to disconnect again
        }
    } catch (error) {
        console.error(error.message)
        Dialog.errorOccurred('Failed to load workers. Please try again.')
        disconnectInfiniteScroll(container) // Ensure cleanup on error
    } finally {
        Loader.delete()
    }
}
