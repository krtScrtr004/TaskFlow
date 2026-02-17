import { searchWorkerEvent } from './Search.js'
import { infiniteScrollWorkers } from './InfiniteScroll.js'
import { selectedUsers } from './Select.js'
import { hideModal } from '../../Utility/HideModal.js'
import { toggleElementClass } from '../../Utility/Utility.js'

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

/**
 * Initializes the add worker modal with all necessary event handlers.
 * 
 * @param {string} projectId - The project ID context
 * @param {string} endpoint - The API endpoint to fetch workers from
 * @param {HTMLElement|null} workerListContainer - Optional container for the worker list
 */
export function initializeAddWorkerModal(projectId, endpoint, workerListContainer = null) {
    searchWorkerEvent(projectId, endpoint, { workerListContainer: workerListContainer })
    infiniteScrollWorkers(projectId, endpoint, '', { workerListContainer: workerListContainer })
    cancelAddWorkerModal()

    hideModal(addWorkerModalTemplate).create(addWorkerModalTemplate.querySelector('#confirm_add_worker_button'))
}

/**
 * Handles the cancel button click event to close the modal and reset state.
 */
export function cancelAddWorkerModal() {
    hideModal(addWorkerModalTemplate).create(
        addWorkerModalTemplate.querySelector('#cancel_add_worker_button'),
        () => { cleanUp() }
    )
}

/**
 * Clean up the "add worker" modal UI and state.
 *
 * @returns {void}
 */
export function cleanUp() {
    const workerContainer = addWorkerModalTemplate.querySelector('.worker-list > .list')
    if (workerContainer) workerContainer.textContent = ''
    selectedUsers.clear()

    const searchBarForm = addWorkerModalTemplate.querySelector('form.search-bar')
    searchBarForm.reset()
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
export function toggleNoWorkerWall(show, container) {
    const noWorkersWall = container.querySelector('.no-workers-wall')
    const listContainer = container.querySelector('.list')

    if (show) {
        toggleElementClass(noWorkersWall, ['flex-col'], ['no-display'])
        toggleElementClass(listContainer, ['no-display'], ['flex-col'])
    } else {
        toggleElementClass(noWorkersWall, ['no-display'], ['flex-col'])
        toggleElementClass(listContainer, ['flex-col'], ['no-display'])
    }
}