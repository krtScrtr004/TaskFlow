import { Loader } from '../../../../render/loader.js'
import { Dialog } from '../../../../render/dialog.js'
import { handleException } from '../../../../utility/handle-exception.js'
import { fetchWorkers } from '../../fetch.js'
import { createWorkerListCard } from '../../render.js'
import { selectWorker } from '../../select.js'
import { initializeAddWorkerModal } from '../../modal.js'

const viewTaskInfo = document.querySelector('.view-task-info')
const addWorkerButton = viewTaskInfo?.querySelector('#add_worker_button')
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
const projectId = viewTaskInfo.dataset.projectid
if (!projectId || projectId.trim() === '') {
    console.error('Project ID not found.')
    Dialog.somethingWentWrong()
}

if (addWorkerModalTemplate) {
    addWorkerButton.addEventListener('click', async () => {
        initializeAddWorkerModal(projectId)

        addWorkerModalTemplate.classList.add('flex-col')
        addWorkerModalTemplate.classList.remove('no-display')

        try {
            const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
            Loader.full(workerList)

            if (!projectId || projectId.trim() === '') {
                throw new Error('Project ID is missing.')
            }

            const workers = await fetchWorkers(projectId)
            workers.forEach(worker => createWorkerListCard(worker))
            selectWorker()
        } catch (error) {
            handleException(error, `Error loading workers: ${error}`)
        } finally {
            Loader.delete()
        }
    })
} else {
    console.error('Add Worker modal template not found.')
}
