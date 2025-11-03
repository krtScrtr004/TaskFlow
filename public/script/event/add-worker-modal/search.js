import { Loader } from '../../render/loader.js'
import { Dialog } from '../../render/dialog.js'
import { debounceAsync } from '../../utility/debounce.js'
import { fetchWorkers } from './fetch.js'
import { createWorkerListCard } from './render.js'
import { toggleNoWorkerWall } from './modal.js'
import { infiniteScrollWorkers, disconnectInfiniteScroll } from './infinite-scroll.js'

let endpoint = ''
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

/**
 * Attaches search event listeners to the worker search form and button within the add worker modal.
 *
 * This function sets up debounced event handlers for submitting the search form and clicking the search button.
 * It validates the provided endpoint, assigns it to a module-level variable, and ensures the required DOM elements exist.
 * When triggered, the event handlers call the asynchronous worker search function with the given project ID.
 *
 * @param {string|number} projectId - The unique identifier of the project to search workers for.
 * @param {string} localEndpoint - The API endpoint to use for searching workers. Must be a non-empty string.
 *
 * @throws {Error} If the provided endpoint is invalid (empty or not provided).
 *
 * @example
 * searchWorkerEvent(123, '/api/workers/search');
 */
export function searchWorkerEvent(projectId, localEndpoint) {
    if (!localEndpoint || localEndpoint.trim() === '') {
        throw new Error('Invalid endpoint provided to searchWorkerEvent.')
    }
    endpoint = localEndpoint

    const searchBarForm = addWorkerModalTemplate?.querySelector('form.search-bar')
    const button = searchBarForm?.querySelector('button')
    if (!searchBarForm) {
        console.error('Search bar form not found.')
        return
    }

    if (!button) {
        console.error('Search button not found.')
        return
    }

    searchBarForm.addEventListener('submit', e => debounceAsync(searchForWorker(e, projectId), 300))
    button.addEventListener('click', e => debounceAsync(searchForWorker(e, projectId), 300))
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
 * @param {Event} e The event object from the search form submission.
 * @param {string} projectId The unique identifier of the project to search workers for.
 * @returns {Promise<void>} Resolves when the search and UI update process is complete.
 */
async function searchForWorker(e, projectId) {
    e.preventDefault()

    const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }

    // Clear the worker list
    workerList.textContent = ''

    // Hide no workers message and show worker list
    toggleNoWorkerWall(false)

    Loader.full(workerList)

    if (!projectId || projectId.trim() === '') {
        console.error('Project ID is missing.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        const searchTerm = document.querySelector('.search-bar input[type="text"]').value.trim()
        const workers = await fetchWorkers(endpoint, searchTerm, 0)

        if (workers && workers.length > 0) {
            workers.forEach(worker => createWorkerListCard(worker))

            // Reset and reinitialize infinite scroll with the search term
            infiniteScrollWorkers(projectId, endpoint, searchTerm)
        } else {
            // Show no workers message if no results
            toggleNoWorkerWall(true)

            // Disconnect infinite scroll observer when no results
            disconnectInfiniteScroll()
        }
    } catch (error) {
        console.error(error.message)
        Dialog.errorOccurred('Failed to load workers. Please try again.')
    } finally {
        Loader.delete()
    }
}
