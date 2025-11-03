import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { fetchWorkers } from './fetch.js'
import { createWorkerListCard } from './render.js'
import { selectedUsers } from './select.js'

let endpoint = ''
let currentInfiniteScrollObserver = null // Store the current observer to allow resetting

export function infiniteScrollWorkers(projectId, localEndpoint, searchKey = '') {
    if (!localEndpoint || localEndpoint.trim() === '') {
        throw new Error('Invalid endpoint provided to infiniteScrollWorkers.')
    }
    endpoint = localEndpoint

    // Disconnect any existing observer before creating a new one
    disconnectInfiniteScroll()

    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    const workerList = addWorkerModalTemplate?.querySelector('.worker-list > .list ')
    const sentinel = addWorkerModalTemplate?.parentElement.querySelector('.sentinel')

    if (!workerList)
        throw new Error('Worker List element not found.')

    if (!sentinel)
        throw new Error('Sentinel element not found.')

    try {
        // Create a new observer with a closure that captures the search key
        const observer = createInfiniteScrollObserver(
            workerList,
            sentinel,
            projectId,
            searchKey
        )

        // Store the observer so we can disconnect it later
        currentInfiniteScrollObserver = { observer, sentinel }
    } catch (error) {
        console.error('Error initializing infinite scroll:', error)
        Dialog.somethingWentWrong()
    }
}

function createInfiniteScrollObserver(workerList, sentinel, projectId, searchKey) {
    let offset = getExistingItemsCount()
    let isLoading = false

    const observer = new IntersectionObserver(async (entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting && !isLoading) {
                isLoading = true
                Loader.trail(workerList)

                try {
                    const workers = await fetchWorkers(endpoint, searchKey, offset)
                    if (workers === false) {
                        // Fetch was already in progress; skip this cycle
                        return
                    }

                    if (!workers || workers.length === 0) {
                        observer.unobserve(sentinel)
                        return
                    }

                    workers.forEach(worker => createWorkerListCard(worker))
                    offset += workers.length
                } catch (error) {
                    console.error('Error during infinite scroll fetch:', error)
                    Dialog.errorOccurred('Failed to load more workers.')
                } finally {
                    isLoading = false
                    Loader.delete()
                }
            }
        }
    })

    observer.observe(sentinel)
    return observer

    function getExistingItemsCount() {
        return workerList.querySelectorAll('.worker-checkbox').length || 0
    }
}

export function disconnectInfiniteScroll() {
    if (currentInfiniteScrollObserver) {
        currentInfiniteScrollObserver.observer.disconnect()
        currentInfiniteScrollObserver = null
    }
}

// Cancel Button -------------------------

function cancelAddWorkerModal(workerContainer = addWorkerModalTemplate.querySelector('.worker-list > .list')) {
    const cancelButton = addWorkerModalTemplate?.querySelector('#cancel_add_worker_button')
    if (!cancelButton) {
        console.error('Cancel button not found.')
        return
    }

    cancelButton.addEventListener('click', () => {
        addWorkerModalTemplate.classList.remove('flex-col')
        addWorkerModalTemplate.classList.add('no-display')

        if (workerContainer) workerContainer.textContent = ''
        selectedUsers.clear()
    })
}