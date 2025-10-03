import { fetchWorkers, createWorkerListCard } from './shared.js'
import { debounce } from '../../../utility/debounce.js'
import { Loader } from '../../../render/loader.js'

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
if (addWorkerModalTemplate) {
    const searchBarForm = addWorkerModalTemplate.querySelector('form.search-bar')
    const button = searchBarForm.querySelector('button')

    async function searchForWorker() {
        const workerList = addWorkerModalTemplate.querySelector('.worker-list')
        const noWorkersWall = workerList.parentElement.querySelector('.no-workers-wall')

        workerList.textContent = ''

        // Hide no workers message and show worker list
        noWorkersWall.classList.remove('flex-col')
        noWorkersWall.classList.add('no-display')

        workerList.classList.add('flex-col')
        workerList.classList.remove('no-display')

        Loader.full(workerList)

        const searchTerm = searchBarForm.querySelector('input[type="text"]').value.trim()

        try {
            const workers = await fetchWorkers(searchTerm)

            if (workers && workers.length > 0) {
                workers.forEach(worker => createWorkerListCard(worker))
            } else {
                // Show no workers message if no results
                noWorkersWall.classList.add('flex-col')
                noWorkersWall.classList.remove('no-display')

                workerList.classList.remove('flex-col')
                workerList.classList.add('no-display')
            }
        } catch (error) {
            console.error(error.message)
        } finally {
            Loader.delete()
        }
    }

    const debounceSearch = debounce(() => {
        searchForWorker();
    }, 300)

    searchBarForm.addEventListener('submit', e => {
        e.preventDefault()
        debounceSearch()
    })
    button.addEventListener('click', debounceSearch)
} else {
    console.error('Add Worker Modal template not found.')
}