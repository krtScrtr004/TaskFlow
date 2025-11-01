import { Dialog } from '../../render/dialog.js'

export const selectedUsers = new Set()
let isSelectWorkerEventInitialized = false

/**
 * Initializes the worker selection event listener.
 * Allows users to select/deselect workers by clicking on the card or checkbox.
 */
export function selectWorker() {
    if (isSelectWorkerEventInitialized) return

    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    const workerList = addWorkerModalTemplate?.querySelector('.worker-list > .list')
    
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }

    workerList.addEventListener('click', e => {
        const workerCheckbox = e.target.closest('.worker-checkbox')
        if (!workerCheckbox) return

        // If the click is directly on the checkbox, let the browser handle toggling
        if (e.target.matches('input[type="checkbox"]')) {
            // Update selectedUsers based on the new checked state
            const checkbox = e.target
            const workerId = checkbox.id
            if (checkbox.checked) {
                if (!selectedUsers.has(workerId)) {
                    selectedUsers.add(workerId)
                }
            } else {
                selectedUsers.delete(workerId)
            }
            return
        }

        // Otherwise, toggle the checkbox manually
        e.stopPropagation()
        e.preventDefault()
        const checkbox = workerCheckbox.querySelector('input[type="checkbox"]')
        if (!checkbox) return
        
        checkbox.checked = !checkbox.checked
        const workerId = checkbox.id
        
        if (checkbox.checked) {
            if (!selectedUsers.has(workerId)) {
                selectedUsers.add(workerId)
            }
        } else {
            selectedUsers.delete(workerId)
        }
    })

    isSelectWorkerEventInitialized = true
}
