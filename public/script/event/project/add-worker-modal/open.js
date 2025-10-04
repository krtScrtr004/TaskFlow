import { fetchWorkers, createWorkerListCard, selectWorker } from './shared.js'
import { Loader } from '../../../render/loader.js'
import { Dialog } from '../../../render/dialog.js'

const addWorkerButton = document.querySelector('#add_worker_button')
if (addWorkerButton) {
    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    if (!addWorkerModalTemplate) {
        console.error('Add Worker modal template not found.')
        Dialog.somethingWentWrong()
    } else {
        addWorkerButton.addEventListener('click', async () => {
            addWorkerModalTemplate.classList.add('flex-col')
            addWorkerModalTemplate.classList.remove('no-display')

            try {
                const workerList = addWorkerModalTemplate.querySelector('.worker-list')
                Loader.full(workerList)

                const workers = await fetchWorkers()
                workers.forEach(worker => createWorkerListCard(worker))
                selectWorker()
            } catch (error) {
                console.error(error.message)
                Dialog.errorOccurred('Failed to load workers. Please try again.')
            } finally {
                Loader.delete()
            }
        })
    }
} else {
    console.error('Add Worker button not found.')
    Dialog.somethingWentWrong()
}



