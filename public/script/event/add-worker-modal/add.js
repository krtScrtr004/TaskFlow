import { selectedUsers } from './shared.js'
import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'

let isLoading = false
async function sendToBackend(projectId, workerIds) {
    if (isLoading) return
    isLoading = true

    const response = await Http.POST('add-worker', { projectId, workerIds })
    if (!response) {
        throw new Error('Failed to add workers to project.')
    }

    isLoading = false
}

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
if (addWorkerModalTemplate) {
    const confirmAddWorkerButton = addWorkerModalTemplate.querySelector('#confirm_add_worker_button')
    if (!confirmAddWorkerButton) {
        console.error('Confirm Add Worker button not found.')
        Dialog.somethingWentWrong()
    } else {
        confirmAddWorkerButton.addEventListener('click', async () => {
            if (selectedUsers.length === 0) {
                Dialog.errorOccurred('No workers selected. Please select at least one worker to add.')
                return
            }

            if (!await confirmationDialog(
                'Add Workers',
                `Are you sure you want to add ${selectedUsers.length} worker(s) to this project?`,
            )) return

            const addWorkerButton = document.querySelector('#add_worker_button')
            const projectId = addWorkerButton.dataset.projectid
            if (!projectId) {
                console.error('Project ID not found in modal dataset.')
                Dialog.somethingWentWrong()
                return
            }

            Loader.patch(confirmAddWorkerButton.querySelector('.text-w-icon'))
            try {
                await sendToBackend(projectId, selectedUsers)
            } catch (error) {
                console.error(error)
                Dialog.errorOccurred('An error occurred while adding workers. Please try again.')
            } finally {
                Loader.delete()
            }
        })
    }
} else {
    console.error('Add Worker Modal template not found.')
    Dialog.somethingWentWrong()
}
