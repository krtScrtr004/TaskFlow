import { infiniteScroll } from '../../../../utility/infinite-scroll.js'
import { createWorkerFetcher } from './fetch.js'
import { render } from './render.js'

const workersSection = document.querySelector('#project_form #workers_section')
if (!workersSection) {
    console.warn('Workers section element not found')
}

const workerPoolList = workersSection.querySelector('.worker-pool-listing .list')
if (!workerPoolList) {
    console.warn('Worker pool list element not found')
}

const sentinel = workersSection.querySelector('.sentinel')
if (!sentinel) {
    console.warn('Sentinel element not found')
}

/**
 * Initialize infinite scrolling for the worker pool list.
 *
 * This function creates a worker fetcher using the provided endpoint and
 * wires it into a generic infiniteScroll utility. It configures:
 *  - the container element that holds worker rows (workerPoolList),
 *  - the sentinel element used to detect scroll-to-end (sentinel),
 *  - a fetch callback that calls the worker fetcher with an offset,
 *  - a render callback that appends rendered worker rows into the container,
 *  - an initial selected-worker count computed from '.selected-worker-row' elements.
 *
 * Note: This function relies on externally-scoped symbols:
 *  - createWorkerFetcher, infiniteScroll, workerPoolList, sentinel, render.
 *
 * @param {string} endpoint URL or API endpoint used by the worker fetcher
 * @returns {void}
 */
export function initializeInfiniteScroll(endpoint) {
    const fetcherFunction = createWorkerFetcher(endpoint)

    infiniteScroll(
        workerPoolList,
        sentinel,
        (offset) => fetcherFunction(null, { offset: offset }),
        (worker) => { workerPoolList.appendChild(render(worker)) },
        workerPoolList.querySelectorAll('.selected-worker-row').length ?? 0
    )
}