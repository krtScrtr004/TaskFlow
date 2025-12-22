import { createWorkerListCard } from './create-worker-list-card.js'
import { infiniteScrollWorkers } from '../add-worker-modal/infinite-scroll.js'
import { searchWorkerEvent } from '../add-worker-modal/search.js'
import { toggleNoWorkerWall } from '../add-worker-modal/modal.js'

const projectContainer = document.querySelector('.project-container')

const projectWorker = projectContainer?.querySelector('.project-workers')
if (!projectWorker) {
    console.warn('Project workers element not found.')
}

const searchBarForm = projectWorker.querySelector('form.search-bar')
if (!searchBarForm) {
    console.error('Search bar form not found in project workers.')
}

const searchWorkerButton = searchBarForm.querySelector('#search_bar_button')
if (!searchWorkerButton) {
    console.error('Search worker button not found in search bar form.')
}

const projectId = projectContainer.dataset.projectid
if (!projectId || projectId.trim() === '') {
    console.error('Project ID not found in project dataset.')
}

const workerList = projectWorker.querySelector('.worker-list > .list')
if (!workerList) {
    console.warn('Worker list container not found.')
}

function getCardsCount() {
    return workerList.querySelectorAll('.user-list-card').length
}

function searchEvent() {
    const params = new URLSearchParams()
    params.append('status', 'assigned')
    const endpoint = `projects/${projectId}/workers`

    const searchInput = searchBarForm.querySelector('#search_bar_input')
    const searchKey = searchInput.value.trim()

    const workerListCount = getCardsCount()
    if (workerListCount > 0 && searchKey !== '') {
        params.set('offset', workerListCount)
    }

    const options = {
        workerListContainer: projectWorker,
        renderer: (worker) => {
            return createWorkerListCard(worker)
        }
    }

    searchWorkerEvent(projectId, `${endpoint}?${params.toString()}`, options)
    infiniteScrollWorkers(projectId, `${endpoint}?${params.toString()}`, searchKey, options)
}
searchEvent()

const initialCardCount = getCardsCount()
if (initialCardCount < 1) {
    toggleNoWorkerWall(true, projectWorker)
}