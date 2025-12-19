import { createFullName } from '../../../utility/utility.js'
import { Loader } from '../../../render/loader.js'
import { handleException } from '../../../utility/handle-exception.js'
import { debounceAsync } from '../../../utility/debounce.js'
import { createWorkerFetcher } from './fetch-workers.js'

let endpoint = null

const workersSection = document.querySelector('#workers_section')
const noWorkersWall = workersSection.querySelector('.no-workers-wall')

const searchBarForm = workersSection.querySelector('.search-bar')
if (!searchBarForm) {
    console.warn('Search bar not found in workers section.')
}

const searchButton = searchBarForm.querySelector('#search_bar_button')
if (!searchButton) {
    console.warn('Search button not found in workers section.')
}

export function initializeSearch(endpointParam) {
    if (!endpointParam) {
        throw new Error('Endpoint parameter is required to initialize search workers.')
    }
    endpoint = endpointParam

    const submitHandler = (e) => debounceAsync(submit(e), 300)

    searchBarForm?.addEventListener('submit', submitHandler)
    searchButton?.addEventListener('click', submitHandler)

    searchButton?.click() // Trigger initial load
}

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

function rebuildEndpointWithSearchTerm(baseEndpoint, term) {
    const params = new URLSearchParams(baseEndpoint.split('?')[1] || '')
    if (term) {
        params.set('key', term)
    } else {
        params.delete('key')
    }
    return `${baseEndpoint.split('?')[0]}?${params.toString()}`
}

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
        button.dataset.workerId = worker.id ?? worker.workerId
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