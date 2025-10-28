import { fetchWorkers, createWorkerListCard, selectWorker, initializeAddWorkerModal } from '../shared.js'
import { Loader } from '../../../render/loader.js'
import { Dialog } from '../../../render/dialog.js'

const projectContainer = document.querySelector('.project-container')
const addWorkerButton = document.querySelector('#add_worker_button')
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
const projectId = projectContainer.dataset.projectid
if (!projectId || projectId.trim() === '') {
    console.error('Project ID not found.')
    Dialog.somethingWentWrong()
}

if (addWorkerModalTemplate) {
    addWorkerButton.addEventListener('click', async () => {
        initializeAddWorkerModal(projectId, 'users')

        addWorkerModalTemplate.classList.add('flex-col')
        addWorkerModalTemplate.classList.remove('no-display')

        try {
            const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
            Loader.full(workerList)

            if (!projectId || projectId.trim() === '') {
                throw new Error('Project ID is missing.')
            }

            const workers = await fetchWorkers(
                projectId,
                null,
                0,
                'users'
            )
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


