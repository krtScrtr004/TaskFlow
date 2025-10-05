import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'

export const selectedUsers = []

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

let isFetchingWorkers = false
export async function fetchWorkers(key = null) {
    if (isFetchingWorkers) return
    isFetchingWorkers = true

    const param = (key) ? key : ''
    const response = await Http.GET('get-worker-info/' + param)
    if (!response) {
        throw new Error('Workers data not found!')
    }

    isFetchingWorkers = false
    return response.data
}

export function createWorkerListCard(worker) {
    const ICON_PATH = 'asset/image/icon/'

    const workerList = addWorkerModalTemplate.querySelector('.worker-list')

    const html = `
        <div class="worker-checkbox flex-row flex-child-center-h ">
            <input type="checkbox" name="${worker.id}" id="${worker.id}">

            <label for="${worker.id}" class="worker-list-card" data-id="${worker.id}">
                <div class="flex-col flex-child-center-v">
                    <img src="${worker.profilePicture || ICON_PATH + 'profile_w.svg'}" alt="${worker.name}" title="${worker.name}" height="40">
                </div>

                <div class="flex-col">
                    <div>
                        <h4 class="wrap-text">${worker.name}</h4>
                        <p><em>${worker.id}</em></p>
                    </div>

                    <div class="job-titles flex-row flex-wrap">
                        ${(worker.jobTitles && worker.jobTitles.length > 0)
            ? worker.jobTitles.map(title => `<span class="job-title-chip">${title}</span>`).join(' ')
            : '<span class="no-job-title-badge">No Job Titles</span>'}
                    </div>
                </div>
            </label>
        </div>`
    workerList.insertAdjacentHTML('afterbegin', html)
}

let isSelectWorkerEventInitialized = false
export function selectWorker() {
    if (isSelectWorkerEventInitialized) return

    const workerList = addWorkerModalTemplate.querySelector('.worker-list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }

    // Use event delegation but be more specific about what triggers the action
    workerList.addEventListener('click', e => {
        // Only proceed if clicked on checkbox, label, or worker-checkbox div
        const workerCheckbox = e.target.closest('.worker-checkbox')
        if (!workerCheckbox) return

        // Prevent multiple triggers
        e.stopPropagation()
        e.preventDefault()

        const checkbox = workerCheckbox.querySelector('input[type="checkbox"]')
        if (!checkbox) return

        // Toggle the checkbox state
        const wasChecked = checkbox.checked
        checkbox.checked = !wasChecked

        // Update selectedUsers array
        const workerId = checkbox.id
        if (checkbox.checked) {
            if (!selectedUsers.includes(workerId)) {
                selectedUsers.push(workerId)
            }
        } else {
            const index = selectedUsers.indexOf(workerId)
            if (index > -1) {
                selectedUsers.splice(index, 1)
            }
        }
        console.table(selectedUsers)
    })

    isSelectWorkerEventInitialized = true
}

let isSendingToBackend = false
async function sendToBackend(projectId, workerIds) {
    if (isSendingToBackend) return
    isSendingToBackend = true

    const response = await Http.POST('add-worker', { projectId, workerIds })
    if (!response) {
        throw new Error('Failed to add workers to project.')
    }

    isSendingToBackend = false
}

export async function addWorker(action = () => {}) {
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
            if (typeof action === 'function') action()
        } catch (error) {
            console.error(error)
            Dialog.errorOccurred('An error occurred while adding workers. Please try again.')
        } finally {
            Loader.delete()
        }
    })
}
