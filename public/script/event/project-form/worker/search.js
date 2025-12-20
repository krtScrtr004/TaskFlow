import { createFullName } from '../../../utility/utility.js'
import { Loader } from '../../../render/loader.js'
import { debounceAsync } from '../../../utility/debounce.js'
import { createWorkerFetcher } from './fetch.js'

let endpoint = null

const workersSection = document.querySelector('#workers_section')
const noWorkersWall = workersSection.querySelector('.worker-pool-listing .no-workers-wall')

const searchBarForm = workersSection.querySelector('.search-bar')
if (!searchBarForm) {
    console.warn('Search bar not found in workers section.')
}

const searchButton = searchBarForm.querySelector('#search_bar_button')
if (!searchButton) {
    console.warn('Search button not found in workers section.')
}

/**
 * Initializes search workers by configuring the search endpoint and wiring UI event handlers.
 *
 * This function assigns the provided endpoint to the module-level `endpoint` variable,
 * creates a debounced submit handler that rate-limits calls to `submit(e)` using
 * `debounceAsync` with a 300ms delay, and attaches that handler to the search form's
 * "submit" event and the search button's "click" event (if those elements exist).
 * Finally, it programmatically triggers an initial search by invoking `searchButton.click()`
 * when the search button is present.
 *
 * The implementation uses optional chaining when registering listeners so it tolerates
 * missing DOM elements (no-op if `searchBarForm` or `searchButton` are undefined).
 *
 * @param {string} endpointParam The search API endpoint (required). Assigned to the module-level `endpoint`.
 *
 * @throws {Error} If `endpointParam` is falsy; an endpoint is required to initialize the search.
 *
 * @returns {void}
 */
export function initializeSearch(endpointParam) {
    if (!endpointParam) {
        throw new Error('Endpoint parameter is required to initialize search workers.')
    }
    endpoint = endpointParam

    const handler = e => debounceAsync(submit(e), 300)
    searchBarForm?.addEventListener('submit', handler)
    searchButton?.addEventListener('click', handler)

    searchButton?.click() // Trigger initial load
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
 *  - Mutates `endpoint` by calling `rebuildEndpointWithSearchTerm(endpoint, searchTerm)`.
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

    const workerList = workersSection.querySelector('.worker-pool-listing .list')
    if (!workerList) {
        throw new Error('Worker list container not found in workers section.')
    }
    workerList.innerHTML = ''

    const searchInput = searchBarForm.querySelector('#search_bar_input')
    if (!searchInput) {
        throw new Error('Search input field not found in search bar form.')
    }

    // Append search term to endpoint
    const searchTerm = searchInput.value.trim()
    endpoint = rebuildEndpointWithSearchTerm(endpoint, searchTerm)

    try {
        Loader.full(workerList)

        const fetchWorkers = createWorkerFetcher() // Create a new fetcher instance
        const workers = await fetchWorkers(endpoint)
        if (workers.length === 0) {
            noWorkersWall?.classList.add('flex-col')
            noWorkersWall?.classList.remove('no-display')
            return
        }

        workers.forEach(worker => {
            const workerCard = renderWorkerPoolCard(worker)
            workerList.appendChild(workerCard)
        })

        noWorkersWall?.classList.add('no-display')
        noWorkersWall?.classList.remove('flex-col')
    } catch (error) {
        throw error
    } finally {
        Loader.delete()
    }
}

/**
 * Rebuilds an endpoint URL by adding, updating, or removing the "key" query parameter.
 *
 * This function parses the query portion of baseEndpoint (if any), preserves all
 * existing query parameters, and then ensures the "key" parameter reflects the
 * provided term:
 *      - If term is a non-empty value, "key" is set to that value.
 *      - If term is falsy (undefined, null, empty string, etc.), the "key" parameter
 *        is removed from the query string.
 *
 * The function returns the base path followed by '?' and the serialized query string.
 * Note: if baseEndpoint contains a fragment (#) after the query string it will be
 * treated as part of the query and percent-encoded; if no query parameters remain,
 * the returned string will include a trailing '?'.
 *
 * @param {string} baseEndpoint The original endpoint URL (may include an existing query string)
 * @param {string} [term] Search term to set as the 'key' parameter; falsy values remove 'key'
 * @return {string} The reconstructed endpoint with the updated query string
 */
function rebuildEndpointWithSearchTerm(baseEndpoint, term) {
    const params = new URLSearchParams(baseEndpoint.split('?')[1] || '')
    if (term) {
        params.set('key', term)
    } else {
        params.delete('key')
    }
    return `${baseEndpoint.split('?')[0]}?${params.toString()}`
}

/**
 * Renders a worker pool card as a list item.
 *
 * Creates and returns an HTMLLIElement containing a button-styled card with:
 *  - an avatar <img> (uses worker.profileLink or a fallback icon),
 *  - a name <span> (built via createFullName(firstName, middleName, lastName) or 'Unknown'),
 *  - up to three deduplicated role chips collected from worker.primaryRole, worker.role, and worker.roles.
 *
 * Structure:
 *  <li>
 *    <button type="button" class="worker-pool-card unset-button" data-worker-id="...">
 *      <img class="circle fit-cover" alt="..." height="55" src="..." />
 *      <div class="flex-col flex-child-start-h worker-info">
 *        <span class="name">...</span>
 *        <div class="flex-row flex-wrap">
 *          <span class="role-chip chip badge light-text">Role</span>
 *          ...
 *        </div>
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
 * @param {string[]} [worker.roles] - Array of role strings to render as chips.
 * @param {string} [worker.role] - Single role string to include among chips.
 * @param {string} [worker.primaryRole] - Primary role string; prioritized when ordering chips.
 *
 * @returns {HTMLLIElement} The constructed <li> element containing the worker card.
 *
 * @throws {Error} If the worker argument is missing or falsy.
 *
 * Notes:
 * - Role values are deduplicated and trimmed to a maximum of 3 chips.
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
    if (worker && (worker.id || worker.workerId)) {
        button.dataset.workerid = worker.id ?? worker.workerId
    }

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

    const roleContainer = document.createElement('div')
    roleContainer.className = 'flex-row flex-wrap'

    // Collect roles from different possible shapes: role string or roles array
    const roles = []
    if (worker) {
        if (Array.isArray(worker.roles)) {
            roles.push(...worker.roles)
        }
        if (worker.role && typeof worker.role === 'string') {
            roles.unshift(worker.role)
        }
        if (worker.primaryRole && typeof worker.primaryRole === 'string') {
            roles.unshift(worker.primaryRole)
        }
    }

    // Deduplicate and render up to 3 chips
    Array.from(new Set(roles)).slice(0, 3).forEach(r => {
        const roleSpan = document.createElement('span')
        roleSpan.className = 'role-chip chip badge light-text'
        roleSpan.textContent = r
        roleContainer.appendChild(roleSpan)
    })

    infoDiv.appendChild(nameSpan)
    infoDiv.appendChild(roleContainer)

    button.appendChild(img)
    button.appendChild(infoDiv)
    li.appendChild(button)

    return li
}