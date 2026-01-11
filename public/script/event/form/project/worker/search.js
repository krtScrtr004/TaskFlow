import { createFullName, die } from '../../../../utility/utility.js'
import { Loader } from '../../../../render/loader.js'
import { debounceAsync } from '../../../../utility/debounce.js'
import { createWorkerFetcher, rebuildEndpointWithParams } from './fetch.js'
import { addedWorkers } from '../record-changes.js'
import { initializeInfiniteScroll } from './infinite-scroll.js'

let endpoint = null

const workersSection = document.querySelector('#workers_section')
const noWorkersWall = workersSection.querySelector('.worker-pool-listing .no-workers-wall')

const searchBarForm = workersSection.querySelector('.search-bar')
if (!searchBarForm) {
    die('Search bar not found in workers section.')
}

const searchButton = searchBarForm.querySelector('#search_bar_button')
if (!searchButton) {
    die('Search button not found in workers section.')
}

const workerList = workersSection.querySelector('.worker-pool-listing .list')
if (!workerList) {
    throw new Error('Worker list container not found in workers section.')
}


/**
 * Initializes the worker search functionality for the project form.
 *
 * Sets up event listeners on the search form and button to handle search submissions
 * with debounced asynchronous requests. Also initializes infinite scroll for search results.
 * Triggers an initial search on load.
 *
 * @param {string} endpointParam - The API endpoint URL to use for searching workers.
 * @throws {Error} If the endpoint parameter is not provided.
 */
export function initializeSearch(endpointParam) {
    if (!endpointParam) {
        throw new Error('Endpoint parameter is required to initialize search workers.')
    }
    endpoint = endpointParam

    const handler = e => debounceAsync(submit(e), 300)
    searchBarForm.addEventListener('submit', handler)
    searchButton.addEventListener('click', handler)

    initializeInfiniteScroll(endpoint)

    searchButton?.click()
}

/**
 * Handles the worker search form submission, fetches matching workers, and updates the UI.
 *
 * This async handler prevents the form's default submission, validates required globals and DOM
 * containers, clears the current worker listing, appends the trimmed search term to the search
 * endpoint (mutating the shared `endpoint` variable), and then performs an async fetch of
 * workers using a newly created fetcher from `createWorkerFetcher()`. While loading, a full
 * loader is shown on the worker list container and it is removed in the finally block.
 *
 * After fetching:
 *  - If no workers are returned, the "no workers" wall is shown.
 *  - If workers are returned, a card is rendered for each worker via `renderWorkerPoolCard`
 *    and appended to the worker list; the "no workers" wall is hidden.
 *
 * Errors thrown during validation or fetching are re-thrown so callers can handle them.
 *
 * Side effects:
 *  - Mutates `endpoint` by calling `rebuildEndpointWithParams(endpoint, { key: searchTerm })`.
 *  - Reads/writes DOM under `workersSection` and `searchBarForm`.
 *  - Shows/hides `noWorkersWall` and uses `Loader.full()` / `Loader.delete()`.
 *  - Uses `createWorkerFetcher()` to obtain the fetch function and `renderWorkerPoolCard()` to
 *    create DOM nodes for each worker.
 *
 * @async
 * @param {Event} e The submit event from the search form (typically a FormEvent or SubmitEvent).
 * @throws {Error} If the global search endpoint is not defined.
 * @throws {Error} If the worker list container is not found inside `workersSection`.
 * @throws {Error} If the search input field is not found in `searchBarForm`.
 * @throws {Error|any} Rethrows any error produced by the worker fetcher or rendering logic.
 * @returns {Promise<void>} Resolves when UI update and cleanup are complete.
 */
async function submit(e) {
    e.preventDefault()
    if (!endpoint) {
        throw new Error('Endpoint is not defined for searching workers.')
    }

    const searchInput = searchBarForm.querySelector('#search_bar_input')
    if (!searchInput) {
        throw new Error('Search input field not found in search bar form.')
    }

    // Append search term to endpoint
    const searchTerm = searchInput.value.trim()
    endpoint = rebuildEndpointWithParams(endpoint, { key: searchTerm })

    try {
        Loader.full(workerList)

        workerList.innerHTML = '' // Clear existing workers

        const fetchWorkers = createWorkerFetcher() // Create a new fetcher instance
        const workers = await fetchWorkers(endpoint)
        if (workers.length === 0) {
            toggleNoWorkersWall(true)
            return
        }

        workers.forEach(worker => {
            const workerCard = renderWorkerPoolCard(worker)
            workerList.appendChild(workerCard)
        })

        toggleNoWorkersWall(false)
    } catch (error) {
        throw error
    } finally {
        Loader.delete()
    }
}

/**
 * Renders a worker pool card as a list item.
 *
 * Creates and returns an HTMLLIElement containing a button-styled card with:
 *  - an avatar <img> (uses worker.profileLink or a fallback icon),
 *  - a name <span> (built via createFullName(firstName, middleName, lastName) or 'Unknown'),
 *
 * Structure:
 *  <li>
 *    <button type="button" class="worker-pool-card unset-button" data-worker-id="...">
 *      <img class="circle fit-cover" alt="..." height="55" src="..." />
 *      <div class="flex-col flex-child-start-h worker-info">
 *        <span class="name">...</span>
 *      </div>
 *    </button>
 *  </li>
 *
 * @param {Object} worker - Worker data object (required).
 * @param {string|number} [worker.id] - Primary worker identifier; used for data-worker-id if present.
 * @param {string|number} [worker.workerId] - Alternate worker identifier; used when worker.id is absent.
 * @param {string} [worker.profileLink] - URL to the worker's avatar image. Falls back to '/public/asset/image/icon/profile_w.svg'.
 * @param {string} [worker.name] - Full display name used for the img alt attribute when available.
 * @param {string} [worker.firstName] - First name part; passed to createFullName().
 * @param {string} [worker.middleName] - Middle name part; passed to createFullName().
 * @param {string} [worker.lastName] - Last name part; passed to createFullName().
 *
 * @returns {HTMLLIElement} The constructed <li> element containing the worker card.
 *
 * @throws {Error} If the worker argument is missing or falsy.
 *
 * Notes:
 * - The created element is not appended to the DOM; the caller must insert it where needed.
 * - This function relies on a global helper createFullName(firstName, middleName, lastName) to compute the displayed name.
 */
function renderWorkerPoolCard(worker) {
    if (!worker) {
        throw new Error('Worker data is required to render worker pool card.')
    }
    const ICON_PATH = '/public/asset/image/icon/'

    const li = document.createElement('li')

    const button = document.createElement('button')
    button.type = 'button'
    button.className = 'worker-pool-card unset-button'
    if (addedWorkers.has(String(worker.id || worker.workerId))) {
        button.classList.add('selected')
    }
    button.dataset.workerid = worker.id ?? worker.workerId

    const img = document.createElement('img')
    img.className = 'circle fit-cover'
    img.alt = worker && worker.name ? worker.name : ''
    img.height = 55
    img.src = worker.profileLink || ICON_PATH + 'profile_w.svg'

    const infoDiv = document.createElement('div')
    infoDiv.className = 'flex-col flex-child-start-h worker-info'

    const nameSpan = document.createElement('span')
    nameSpan.className = 'name'
    nameSpan.textContent = createFullName(worker.firstName, worker.middleName, worker.lastName) || 'Unknown'

    infoDiv.appendChild(nameSpan)

    button.appendChild(img)
    button.appendChild(infoDiv)
    li.appendChild(button)

    return li
}

/**
 * Toggle the visibility of the "no workers" wall element.
 *
 * When `show` is true, the function makes the element visible by adding
 * the 'flex-col' class and removing 'no-display'. When `show` is false,
 * it hides the element by adding 'no-display' and removing 'flex-col'.
 * The operation is safe — if `noWorkersWall` is undefined or null, the
 * function performs no action.
 *
 * @param {boolean} show True to show the no-workers wall, false to hide it.
 * @returns {void}
 */
function toggleNoWorkersWall(show) {
    if (show) {
        noWorkersWall?.classList.add('flex-col')
        noWorkersWall?.classList.remove('no-display')
    } else {
        noWorkersWall?.classList.add('no-display')
        noWorkersWall?.classList.remove('flex-col')
    }
}