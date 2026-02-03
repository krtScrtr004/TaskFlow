import { createWorkerListCard } from './CreateWorkerListCard.js'
import { infiniteScrollWorkers } from '../AddWorkerModal/InfiniteScroll.js'
import { searchWorkerEvent } from '../AddWorkerModal/Search.js'
import { toggleNoWorkerWall } from '../AddWorkerModal/Modal.js'

const projectContainer = document.querySelector('.project-container')

const projectWorker = projectContainer?.querySelector('.project-workers')
if (!projectWorker) console.warn('Project workers element not found')

const searchBarForm = projectWorker.querySelector('form.search-bar')
if (!searchBarForm) console.error('Search bar form not found in project workers')

const searchWorkerButton = searchBarForm.querySelector('#search_bar_button')
if (!searchWorkerButton) console.error('Search worker button not found in search bar form')

const projectId = projectContainer.dataset.projectid
if (!projectId || projectId.trim() === '') console.error('Project ID not found in project dataset')

const workerList = projectWorker.querySelector('.worker-list > .list')
if (!workerList) console.warn('Worker list container not found')

function getCardsCount() {
    return workerList.querySelectorAll('.user-list-card').length
}

/**
 * Searches for assigned workers for the current project and sets up search + infinite-scroll.
 *
 * This function reads the current search input, builds query parameters (always including
 * status=assigned and optionally an offset based on the current displayed card count),
 * constructs a workers endpoint for the current project, and then invokes two routines:
 * - searchWorkerEvent(...) to perform the initial search/render of results,
 * - infiniteScrollWorkers(...) to enable loading more results as the user scrolls.
 *
 * Behavior and side effects:
 * - Reads the search string from the DOM input element (#search_bar_input) inside searchBarForm.
 * - Uses getCardsCount() to determine an offset when some results are already rendered.
 * - Creates a URLSearchParams instance and appends status='assigned' and offset when applicable.
 * - Builds an endpoint path: `projects/${projectId}/workers` and appends the query string.
 * - Prepares an options object with a workerListContainer and a renderer that calls
 *   createWorkerListCard(worker).
 * - Calls searchWorkerEvent(...) to fetch/render the initial result set.
 * - Calls infiniteScrollWorkers(...) to configure lazy-loading of further results.
 * - Mutates the DOM by populating/updating the configured worker list container.
 * - Depends on externally scoped identifiers and functions (projectId, searchBarForm,
 *   projectWorker, getCardsCount, createWorkerListCard, searchWorkerEvent,
 *   infiniteScrollWorkers). Missing or invalid globals/DOM elements may cause runtime errors.
 *
 * @returns {void}
 * @throws {Error} If required globals (e.g., projectId) or DOM elements (e.g., search input)
 *                 are missing or invalid, underlying calls may throw runtime errors.
 */
function searchEvent() {
    const params = new URLSearchParams()
    params.append('status', 'assigned')
    const endpoint = `projects/${projectId}/workers`

    const searchInput = searchBarForm.querySelector('#search_bar_input')
    const searchKey = searchInput.value.trim()

    const workerListCount = getCardsCount()
    if (workerListCount > 0 && searchKey !== '')
        params.set('offset', workerListCount)

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
if (initialCardCount < 1) toggleNoWorkerWall(true, projectWorker)