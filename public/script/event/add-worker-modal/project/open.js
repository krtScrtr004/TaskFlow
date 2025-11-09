import { Loader } from '../../../render/loader.js'
import { Dialog } from '../../../render/dialog.js'
import { fetchWorkers } from '../fetch.js'
import { createWorkerListCard } from '../render.js'
import { selectWorker } from '../select.js'
import { initializeAddWorkerModal } from '../modal.js'
import { toggleNoWorkerWall } from '../modal.js'
import { handleException } from '../../../utility/handle-exception.js'

const projectContainer = document.querySelector('.project-container')
const addWorkerButton = document.querySelector('#add_worker_button')
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
const thisProjectId = projectContainer ? projectContainer.dataset.projectid : null
if (!thisProjectId || thisProjectId.trim() === '') {
    console.error('Project ID not found.')
    Dialog.somethingWentWrong()
}

if (addWorkerModalTemplate) {
    addWorkerButton?.addEventListener('click', async () => {
        const params = new URLSearchParams()
        params.append('status', 'unassigned')
        params.append('excludeProjectTerminated', 'true')

        const endpoint = `projects/${thisProjectId}/workers?${params.toString()}`

        initializeAddWorkerModal(thisProjectId, endpoint)

        addWorkerModalTemplate.classList.add('flex-col')
        addWorkerModalTemplate.classList.remove('no-display')

        try {
            const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
            Loader.full(workerList)

            const workers = await fetchWorkers(endpoint)
            if (workers.length === 0) {
                toggleNoWorkerWall(true)
                return
            }

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


