import { Dialog } from '../../../Render/Dialog.js'
import { Loader } from '../../../Render/Loader.js'
import { debounceAsync } from '../../../Utility/Debounce.js'
import { handleException } from '../../../Utility/HandleException.js'
import { Http } from '../../../Utility/Http.js'
import { die, querySelectorByRegex } from '../../../Utility/Utility.js'

let isLoading = false

const addWorkerTableTemplate = document.querySelector('#add_worker_table_template')
if (!addWorkerTableTemplate) die('Add worker table wrapper element not found')

const addWorkerTableModal = addWorkerTableTemplate.querySelector('.add-worker-table')
if (!addWorkerTableModal) die('Add worker table modal element not found')

const tableBodySection = addWorkerTableModal.querySelector('table > tbody')
if (!tableBodySection) throw new Error('Table body element is not found')

const saveAddWorkerButton = addWorkerTableModal.querySelector('#save_added_worker_button')
if (!saveAddWorkerButton) throw new Error('Save button not found')
/**
 * Attaches a debounced click handler to the global saveAddWorkerButton that triggers saving selected workers to the provided endpoint.
 *
 * Validates that a non-empty string endpoint is provided and throws an Error otherwise.
 * The attached handler debounces calls to saveAddWorkerButtonEvent by 300ms using debounceAsync,
 * forwarding the original click event and the endpoint to that function.
 *
 * @param {string} endpoint - URL or endpoint identifier to which worker data will be sent.
 * @throws {Error} If endpoint is not provided or is not a non-empty string.
 * @returns {void}
 */
export function saveAddWorkers(endpoint) {
    if (!endpoint || typeof endpoint !== 'string' || endpoint === '') 
        throw new Error('Endpoint is required')

    const handler = e => debounceAsync(saveAddWorkerButtonEvent(e, endpoint), 300)
    saveAddWorkerButton.addEventListener('click', handler)
}

/**
 * Handle the "save add worker" button event: collect selected workers, validate inputs,
 * send the data to the backend, and show user feedback.
 *
 * This async handler prevents the default form/button action, displays a loading indicator,
 * gathers worker IDs and their corresponding default rates from rows within a
 * pre-defined tableBodySection, validates required values, and posts the collected
 * mapping to the given endpoint via sendToBackend. On success it shows a success dialog
 * and reloads the page after a short delay. Any thrown or runtime errors are passed to
 * handleException, and the loader is always removed in a finally block.
 *
 * Expected DOM dependencies:
 *  - saveAddWorkerButton: element containing the triggering button (used for loader UI)
 *  - tableBodySection: container whose child <tr> elements each represent a selected worker
 *  - each worker row must have a data-workerid attribute and an input with id "default_rate"
 *
 * @param {Event} e The DOM event (e.g., click or submit). The default action will be prevented.
 * @param {string} endpoint URL or path to which the collected worker data will be sent.
 *
 * @throws {Error} If a worker row is missing a data-workerid ("Worker ID is required").
 * @throws {Error} If a worker row is missing a default rate value ("Worker default rate is required").
 *
 * @returns {Promise<void>} Resolves when the operation completes (success or handled failure).
 */
async function saveAddWorkerButtonEvent(e, endpoint) {
    e.preventDefault()

    Loader.patch(saveAddWorkerButton.querySelector('.text-w-icon'))
    try {
        const rowData = []

        const selectedWorkerRows = tableBodySection.querySelectorAll('tr')
        selectedWorkerRows.forEach(row => {
            const id = row.dataset.workerid
            if (!id) throw new Error('Worker ID is required')

            const defaultRate = querySelectorByRegex(row, 'input', 'id', new RegExp(/^default_rate/))?.[0].value
            if (!defaultRate) throw new Error('Worker default rate is required')

            rowData.push({id, defaultRate})
        })

        await sendToBackend(endpoint, rowData)

        Dialog.operationSuccess('Workers Added', 'Worker(s) have been added successfully')
        setTimeout(() => window.location.reload(), 3000)

    } catch (error) {
        handleException(error)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends data to a backend endpoint using an HTTP POST request.
 *
 * This async helper prevents concurrent submissions by honoring an external
 * `isLoading` flag (returns early if a request is already in progress),
 * delegates the actual network operation to `Http.POST`, validates that a
 * response was received, and ensures the `isLoading` flag is cleared in all
 * cases. Any errors from the request or a missing response are propagated to
 * the caller.
 *
 * @param {string} endpoint URL or route to which the request will be sent
 * @param {Object} data Payload to be sent in the POST body
 *
 * @throws {Error} If no response is returned from the server or if Http.POST fails
 *
 * @returns {Promise<void>} Resolves when the request completes successfully (no value returned)
 */
async function sendToBackend(endpoint, data) {
    if (isLoading) {
        console.warn('Request already in progress. Please wait.')
        return
    }
    isLoading = true

    try {
        const response = await Http.POST(endpoint, { workers: data })
        if (!response) throw new Error('No response from the server')
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}