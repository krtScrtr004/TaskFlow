import { fetchWorkers, createWorkerListCard, selectWorker, initializeAddWorkerModal } from '../shared.js'
import { Loader } from '../../../render/loader.js'
import { Dialog } from '../../../render/dialog.js'

const projectContainer = document.querySelector('.project-container')
const addWorkerButton = document.querySelector('#add_worker_button')
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

if (addWorkerModalTemplate) {
    addWorkerButton.addEventListener('click', async () => {
        initializeAddWorkerModal(null, 'users')

        addWorkerModalTemplate.classList.add('flex-col')
        addWorkerModalTemplate.classList.remove('no-display')

        try {
            const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
            Loader.full(workerList)

            const workers = await fetchWorkers(
                null,
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


