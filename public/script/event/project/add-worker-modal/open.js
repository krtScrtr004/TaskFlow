import { fetchWorkers, createWorkerListCard, selectWorker } from './shared.js'
import { Loader } from '../../../render/loader.js'

const addWorkerButton = document.querySelector('#add_worker_button')
if (addWorkerButton) {
    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    addWorkerButton.addEventListener('click', async () => {
        addWorkerModalTemplate.classList.add('flex-col')
        addWorkerModalTemplate.classList.remove('no-display')

        try {
            Loader.full(addWorkerModalTemplate.querySelector('.worker-list'))

            const workers = await fetchWorkers()
            workers.forEach(worker => createWorkerListCard(worker))
            selectWorker()
        } catch (error) {
            console.error(error.message)
        } finally {
            Loader.delete()
        }
    })
} else {
    console.error('Add Worker button not found.')
}



