import { Loader } from '../../../render/loader.js'
import { handleException } from '../../../utility/handle-exception.js'
import { fetchWorkers } from '../fetch.js'
import { createWorkerListCard } from '../render.js'
import { selectWorker } from '../select.js'
import { initializeAddWorkerModal } from '../modal.js'
import { die, toggleElementClass } from '../../../utility/utility.js'
import { addedWorkerInfo, oldWorkerInfo } from '../../form/task/record-changes.js'

const taskForm = document.querySelector('#task_form')

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
if (!addWorkerModalTemplate) die('Add worker modal template not found')

const noWorkersWall = addWorkerModalTemplate.querySelector('.no-workers-wall')
if (!noWorkersWall) console.warn('No workers wall element not found')

const projectId = taskForm?.parentElement.dataset.projectid
if (!projectId || projectId.trim() === '') die('Project ID not found')

export function openModal(endpointParams) {
    const addWorkerButton = taskForm?.querySelector('#add_worker_button')
    if (!addWorkerButton) die('Add worker button not found')

    const params = new URLSearchParams()
    for (const [key, value] of Object.entries(endpointParams)) {
        params.append(key, value)
    }

    const endpoint = `projects/${projectId}/tasks/workers?${params.toString()}`

    addWorkerButton.addEventListener('click', async () => {
        // Initialize the add worker modal with the project ID and endpoint
        initializeAddWorkerModal(projectId, endpoint)

        // Show the modal 
        toggleElementClass(addWorkerModalTemplate, ['flex-col'], ['no-display'])

        try {
            const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
            Loader.full(workerList)

            let existingCount = 0 // Hold the number of existing worker from the response

            // Fetch the list of unassigned workers from the server
            const workers = await fetchWorkers(endpoint)

            // Render a card for each worker in the modal
            workers.forEach(worker => {
                toggleElementClass(workerList, ['flex-col'], ['no-display'])
                toggleElementClass(noWorkersWall, ['no-display'], ['flex-col'])

                const isExisting = oldWorkerInfo.has(worker.id) || addedWorkerInfo.has(worker.id)
                if (isExisting) existingCount++
                else createWorkerListCard(worker)
            })

            // If no workers are found or all fetched workers are already exists, show a "no workers" message and exit
            if (workers.length === 0 || workers.length - existingCount === 0) {
                toggleElementClass(workerList, ['no-display'], ['flex-col'])
                toggleElementClass(noWorkersWall, ['flex-col'], ['no-display'])
                return
            }

            // Enable selection functionality for the worker cards
            selectWorker()
        } catch (error) {
            handleException(error, `Error loading workers: ${error}`)
        } finally {
            Loader.delete()
        }
    })

}
