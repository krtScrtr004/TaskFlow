import { createWorkerListCard } from './create-worker-list-card.js'
import { infiniteScrollWorkers } from '../add-worker-modal/infinite-scroll.js'
import { searchWorkerEvent } from '../add-worker-modal/search.js'

document.addEventListener('DOMContentLoaded', () => {
    const projectContainer = document.querySelector('.project-container')

    const projectWorker = projectContainer?.querySelector('.project-workers')
    if (!projectWorker) {
        console.warn('Project workers element not found.')
        return
    }

    const searchBarForm = projectWorker.querySelector('form.search-bar')
    if (!searchBarForm) {
        console.error('Search bar form not found in project workers.')
        return
    }

    const searchWorkerButton = searchBarForm.querySelector('#search_assigned_worker_button')
    if (!searchWorkerButton) {
        console.error('Search worker button not found in search bar form.')
        return
    }

    const projectId = projectContainer.dataset.projectid
    if (!projectId || projectId.trim() === '') {
        console.error('Project ID not found in project dataset.')
        return
    }

    function searchEvent() {
        const params = new URLSearchParams()
        params.append('status', 'assigned')
        const endpoint = `projects/${projectId}/workers`

        const searchInput = searchBarForm.querySelector('#search_assigned_worker')
        const searchKey = searchInput.value.trim()

        const workerListCount = projectWorker.querySelectorAll('.worker-list > .list > .user-list-card').length
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
})
