import { confirmationDialog } from '../../../Render/ConfirmationDialog.js'
import { Dialog } from '../../../Render/Dialog.js'
import { Loader } from '../../../Render/Loader.js'
import { debounceAsync } from '../../../Utility/Debounce.js'
import { handleException } from '../../../Utility/HandleException.js'
import { die } from '../../../Utility/Utility.js'
import { selectedUsers } from '../Select.js'

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
if (!addWorkerModalTemplate) die('Add Worker Modal template not found')

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
    action = null,
    onSuccess = null
) {
    if (!asyncFunction || typeof asyncFunction !== 'function') {
        console.error('Invalid asyncFunction provided to addWorker.')
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

    const cleanup = () => {
        // Close the modal
        const workerContainer = addWorkerModalTemplate.querySelector('.worker-list > .list')
        if (workerContainer) workerContainer.textContent = ''
    }

    try {
        Loader.patch(confirmAddWorkerButton.querySelector('.text-w-icon'))

        if (selectedUsers.size === 0) {
            Dialog.errorOccurred('No workers selected. Please select at least one worker to add.')
            cleanup()
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

        const result = await asyncFunction(projectId, Array.from(selectedUsers.values()))
        if (typeof action === 'function') action(result)

        cleanup()

        if (typeof onSuccess === 'function') {
            (onSuccess.length > 0)
                ? onSuccess(result)
                : onSuccess()
        }
    } catch (error) {
        handleException(error, `Error adding workers: ${error.message}`)
    } finally {
        Loader.delete()
    }
}
