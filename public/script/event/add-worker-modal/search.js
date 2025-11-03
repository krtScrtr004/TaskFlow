import { Loader } from '../../render/loader.js'
import { Dialog } from '../../render/dialog.js'
import { debounceAsync } from '../../utility/debounce.js'
import { fetchWorkers } from './fetch.js'
import { createWorkerListCard } from './render.js'
import { toggleNoWorkerWall } from './modal.js'
import { infiniteScrollWorkers, disconnectInfiniteScroll } from './infinite-scroll.js'

let endpoint = ''
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

export function searchWorkerEvent(projectId, localEndpoint) {
    if (!localEndpoint || localEndpoint.trim() === '') {
        throw new Error('Invalid endpoint provided to searchWorkerEvent.')
    }
    endpoint = localEndpoint

    const searchBarForm = addWorkerModalTemplate?.querySelector('form.search-bar')
    const button = searchBarForm?.querySelector('button')
    if (!searchBarForm) {
        console.error('Search bar form not found.')
        return
    }

    if (!button) {
        console.error('Search button not found.')
        return
    }

    searchBarForm.addEventListener('submit', e => debounceAsync(searchForWorker(e, projectId), 300))
    button.addEventListener('click', e => debounceAsync(searchForWorker(e, projectId), 300))
}

async function searchForWorker(e, projectId) {
    e.preventDefault()

    const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }

    workerList.textContent = ''

    // Hide no workers message and show worker list
    toggleNoWorkerWall(false)

    Loader.full(workerList)

    if (!projectId || projectId.trim() === '') {
        console.error('Project ID is missing.')
        Dialog.somethingWentWrong()
        return
    }

    const searchTerm = document.querySelector('.search-bar input[type="text"]').value.trim()

    try {
        const workers = await fetchWorkers(endpoint, searchTerm, 0)

        if (workers && workers.length > 0) {
            workers.forEach(worker => createWorkerListCard(worker))

            // Reset and reinitialize infinite scroll with the search term
            infiniteScrollWorkers(projectId, endpoint, searchTerm)
        } else {
            // Show no workers message if no results
            toggleNoWorkerWall(true)

            // Disconnect infinite scroll observer when no results
            disconnectInfiniteScroll()
        }
    } catch (error) {
        console.error(error.message)
        Dialog.errorOccurred('Failed to load workers. Please try again.')
    } finally {
        Loader.delete()
    }
}
