import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'
import { searchWorkerEvent } from './search.js'
import { infiniteScrollWorkers } from './infinite-scroll.js'
import { selectedUsers } from './select.js'

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

/**
 * Initializes the add worker modal with all necessary event handlers.
 * 
 * @param {string} projectId - The project ID context
 * @param {string} endpoint - The API endpoint to fetch workers from
 */
export function initializeAddWorkerModal(projectId, endpoint) {
    searchWorkerEvent(projectId, endpoint)
    infiniteScrollWorkers(projectId, endpoint)
    cancelAddWorkerModal()
}

/**
 * Handles the cancel button click event to close the modal and reset state.
 */
export function cancelAddWorkerModal() {
    const workerContainer = addWorkerModalTemplate?.querySelector('.worker-list > .list')
    const cancelButton = addWorkerModalTemplate?.querySelector('#cancel_add_worker_button')
    
    if (!cancelButton) {
        console.error('Cancel button not found.')
        return
    }

    cancelButton.addEventListener('click', () => {
        addWorkerModalTemplate.classList.remove('flex-col')
        addWorkerModalTemplate.classList.add('no-display')

        if (workerContainer) workerContainer.textContent = ''
        selectedUsers.clear()

        const searchBarForm = addWorkerModalTemplate?.querySelector('form.search-bar')
        searchBarForm.reset()
    })
}

/**
 * Sets up the add worker button event handler.
 * 
 * @param {string} projectId - The project ID
 * @param {Function} asyncFunction - The async function to execute when adding workers
 * @param {Function} action - Optional callback to execute with the result
 * @param {Function} onSuccess - Optional success callback
 */
export async function addWorker(
    projectId,
    asyncFunction,
    action = () => { },
    onSuccess = () => { }
) {
    if (!asyncFunction || typeof asyncFunction !== 'function') {
        console.error('Invalid asyncFunction provided to addWorker.')
        return
    }

    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    if (!addWorkerModalTemplate) {
        console.error('Add Worker Modal template not found.')
        Dialog.somethingWentWrong()
        return
    }

    const confirmAddWorkerButton = addWorkerModalTemplate.querySelector('#confirm_add_worker_button')
    if (!confirmAddWorkerButton) {
        console.error('Confirm Add Worker button not found.')
        Dialog.somethingWentWrong()
        return
    }

    confirmAddWorkerButton.addEventListener('click', e => 
        debounceAsync(addWorkerButtonEvent(e, projectId, confirmAddWorkerButton, asyncFunction, action, onSuccess), 300)
    )
}

/**
 * Handles the add worker button click event.
 * 
 * @param {Event} e - The click event
 * @param {string} projectId - The project ID
 * @param {HTMLElement} confirmAddWorkerButton - The confirm button element
 * @param {Function} asyncFunction - The async function to execute
 * @param {Function} action - Optional callback to execute with the result
 * @param {Function} onSuccess - Optional success callback
 */
async function addWorkerButtonEvent(e, projectId, confirmAddWorkerButton, asyncFunction, action, onSuccess) {
    e.preventDefault()
    
    Loader.patch(confirmAddWorkerButton.querySelector('.text-w-icon'))

    if (selectedUsers.size === 0) {
        Dialog.errorOccurred('No workers selected. Please select at least one worker to add.')
        return
    }

    if (!await confirmationDialog(
        'Add Workers',
        `Are you sure you want to add ${selectedUsers.size} worker(s) to this project?`,
    )) return

    if (!projectId) {
        console.error('Project ID not found in modal dataset.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        const result = await asyncFunction(projectId, Array.from(selectedUsers.values()))
        
        if (typeof action === 'function') { 
            action(result)
        }

        // Close the modal
        const cancelButton = addWorkerModalTemplate.querySelector('#cancel_add_worker_button')
        cancelButton?.click()

        if (onSuccess.length > 0) { 
            onSuccess(result)
        } else {
            onSuccess()
        }
    } catch (error) {
        handleException(error, `Error adding workers: ${error.message}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Toggles the visibility of the "No Workers Wall" and the worker list in the Add Worker modal.
 *
 * This function shows or hides the "No Workers Wall" element and the worker list container
 * based on the provided boolean flag. It manipulates CSS classes to control the display:
 * - When `show` is true, the "No Workers Wall" is displayed and the worker list is hidden.
 * - When `show` is false, the worker list is displayed and the "No Workers Wall" is hidden.
 *
 * @param {boolean} show Determines whether to show the "No Workers Wall" (`true`) or the worker list (`false`).
 *
 * @throws Will log an error to the console if the "No Workers Wall" or worker list container elements are not found.
 */
export function toggleNoWorkerWall(show) {
    const noWorkersWall = addWorkerModalTemplate?.querySelector('.no-workers-wall')
    if (!noWorkersWall) {
        console.error('No Worker Wall element not found.')
        return
    }

    const listContainer = addWorkerModalTemplate.querySelector('.worker-list > .list')
    if (!listContainer) {
        console.error('Worker list container not found.')
        return
    }

    if (show) {
        noWorkersWall.classList.add('flex-col')
        noWorkersWall.classList.remove('no-display')

        listContainer.classList.remove('flex-col')
        listContainer.classList.add('no-display')
    } else {
        noWorkersWall.classList.add('no-display')
        noWorkersWall.classList.remove('flex-col')

        listContainer.classList.add('flex-col')
        listContainer.classList.remove('no-display')
    }
}