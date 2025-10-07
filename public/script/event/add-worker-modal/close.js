import { selectedUsers } from './shared.js'

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
const cancelButton = addWorkerModalTemplate?.querySelector('#cancel_add_worker_button')
if (cancelButton) {
    cancelButton.addEventListener('click', () => {
        addWorkerModalTemplate.classList.remove('flex-col')
        addWorkerModalTemplate.classList.add('no-display')

        const workerList = addWorkerModalTemplate.querySelector('.worker-list')
        workerList.textContent = ''
        selectedUsers.length = 0
    })
} else {
    console.error('Cancel button not found.')
}
