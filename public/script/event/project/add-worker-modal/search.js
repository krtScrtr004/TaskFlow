import { fetchWorkers, createWorkerListCard } from './shared.js'
import { debounce } from '../../../utility/debounce.js'
import { Loader } from '../../../render/loader.js'

const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
if (addWorkerModalTemplate) {
    const searchBarForm = addWorkerModalTemplate.querySelector('form.search-bar')
    const button = searchBarForm.querySelector('button')

    async function searchForWorker() {
        const workerList = addWorkerModalTemplate.querySelector('.worker-list')
        workerList.textContent = ''

        Loader.full(workerList)

        const input = searchBarForm.querySelector('input[type="text"]')

        try {
            const workers = await fetchWorkers(input.value.trim())
            workers.forEach(worker => createWorkerListCard(worker))
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