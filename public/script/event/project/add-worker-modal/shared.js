import { Http } from '../../../utility/http.js'
import { Dialog } from '../../../render/dialog.js'

export const selectedUsers = []
let isLoading = false

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

export async function fetchWorkers(key = null) {
    if (isLoading) return
    isLoading = true

    const param = (key) ? key : ''
    const response = await Http.GET('get-worker-info/' + param)
    if (!response) {
        throw new Error('Workers data not found!')
    }

    isLoading = false
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