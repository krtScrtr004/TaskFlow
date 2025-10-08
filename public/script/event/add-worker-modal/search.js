import { fetchWorkers, createWorkerListCard } from './shared.js'
import { debounceAsync } from '../../utility/debounce.js'
import { Loader } from '../../render/loader.js'
import { Dialog } from '../../render/dialog.js'

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
const searchBarForm = addWorkerModalTemplate?.querySelector('form.search-bar')
const button = searchBarForm?.querySelector('button')
if (searchBarForm && button) {
    
    const search = debounceAsync(e => {
        e.preventDefault()
        searchForWorker()
    }, 300)

    searchBarForm.addEventListener('submit', e => search(e))
    button.addEventListener('click', e => search(e))
} else {
    console.warn('Search bar form not found.')
}

async function searchForWorker() {
    const workerList = addWorkerModalTemplate.querySelector('.worker-list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }
    const noWorkersWall = workerList.parentElement.querySelector('.no-workers-wall')

    workerList.textContent = ''

    // Hide no workers message and show worker list
    noWorkersWall?.classList.remove('flex-col')
    noWorkersWall?.classList.add('no-display')

    workerList.classList.add('flex-col')
    workerList.classList.remove('no-display')

    Loader.full(workerList)

    const projectContainer = document.querySelector('.project-container')
    const projectId = projectContainer.dataset.projectid
    if (!projectId || projectId.trim() === '') {
        console.error('Project ID is missing.')
        Dialog.somethingWentWrong()
        return
    }

    const searchTerm = searchBarForm.querySelector('input[type="text"]').value.trim()

    try {
        const workers = await fetchWorkers(projectId, searchTerm)

        if (workers && workers.length > 0) {
            workers.forEach(worker => createWorkerListCard(worker))
        } else {
            // Show no workers message if no results
            noWorkersWall?.classList.add('flex-col')
            noWorkersWall?.classList.remove('no-display')

            workerList.classList.remove('flex-col')
            workerList.classList.add('no-display')
        }
    } catch (error) {
        console.error(error.message)
        Dialog.errorOccurred('Failed to load workers. Please try again.')
    } finally {
        Loader.delete()
    }
}