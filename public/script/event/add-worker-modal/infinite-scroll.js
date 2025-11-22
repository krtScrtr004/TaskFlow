import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { fetchWorkers } from './fetch.js'
import { createWorkerListCard } from './render.js'
import { selectedUsers } from './select.js'

// Map to store observers per container to support multiple simultaneous instances
const observerMap = new WeakMap()

export function infiniteScrollWorkers(projectId, localEndpoint, searchKey = '', options = {}) {
    if (!localEndpoint || localEndpoint.trim() === '') {
        throw new Error('Invalid endpoint provided to infiniteScrollWorkers.')
    }
    
    const container = options.workerListContainer || document.querySelector('#add_worker_modal_template')
    if (!container) {
        throw new Error('Worker List element not found.')
    }

    // Disconnect any existing observer for this specific container
    disconnectInfiniteScroll(container)

    const sentinel = container.querySelector('.sentinel') || container.parentElement?.querySelector('.sentinel')

    if (!sentinel) {
        throw new Error('Sentinel element not found.')
    }

    try {
        // Create a new observer with a closure that captures all context
        const observerContext = createInfiniteScrollObserver(
            container,
            sentinel,
            projectId,
            localEndpoint,
            searchKey,
            options
        )

        // Store the observer for this specific container
        observerMap.set(container, observerContext)
    } catch (error) {
        console.error('Error initializing infinite scroll:', error)
        Dialog.somethingWentWrong()
    }
}

/**
 * Creates an IntersectionObserver to implement infinite scroll for loading workers.
 *
 * This function observes a sentinel element at the end of a worker list and fetches more workers
 * from the server when the sentinel becomes visible (i.e., when the user scrolls near the bottom).
 * It handles loading state, error reporting, and appends new worker cards to the list.
 *
 * @param {HTMLElement} container The container element holding the worker list.
 * @param {HTMLElement} sentinel The DOM element used as the scroll sentinel for triggering loading.
 * @param {string|number} projectId The identifier of the current project (used for fetching workers).
 * @param {string} endpoint The API endpoint to fetch workers from.
 * @param {string} searchKey The current search/filter key for fetching workers.
 * @param {Object} options Optional parameters such as renderer.
 *
 * @returns {Object} Object containing the observer and cleanup function.
 */
function createInfiniteScrollObserver(container, sentinel, projectId, endpoint, searchKey, options = {}) {
    const workerList = container.querySelector('.worker-list > .list') || container
    let offset = getExistingItemsCount(workerList)
    let isObserverActive = true // Track if this observer should still be active
    let isLoading = false // Instance-specific loading state

    const observer = new IntersectionObserver(async (entries) => {
        for (const entry of entries) {
            // Check if observer is still active and not loading
            if (entry.isIntersecting && !isLoading && isObserverActive) {
                isLoading = true
                Loader.trail(workerList)

                try {
                    const workers = await fetchWorkers(endpoint, searchKey, offset)
                    
                    // Check again after async operation
                    if (!isObserverActive) {
                        return // Observer was disconnected during fetch
                    }

                    if (workers === false) {
                        // Fetch was already in progress; skip this cycle
                        continue
                    }

                    // No more workers to load; stop observing
                    if (!workers || workers.length === 0) {
                        observer.unobserve(sentinel)
                        isObserverActive = false
                        return
                    }

                    workers.forEach(worker => options.renderer ? options.renderer(worker) : createWorkerListCard(worker))
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
    
    // Return both observer and a cleanup function
    return {
        observer,
        cleanup: () => {
            isObserverActive = false
            observer.disconnect()
        }
    }

    function getExistingItemsCount(list) {
        return list.querySelectorAll('.worker-checkbox, .user-list-card').length || 0
    }
}

/**
 * Disconnects the infinite scroll observer for a specific container.
 *
 * - If container is provided, disconnects only that container's observer.
 * - If no container is provided, does nothing (each container manages its own observer).
 * - Calls cleanup function to mark observer as inactive and disconnect it.
 * - Removes the observer from the WeakMap to prevent memory leaks.
 *
 * @param {HTMLElement} container The container whose observer should be disconnected.
 */
export function disconnectInfiniteScroll(container) {
    if (!container) return
    
    const observerContext = observerMap.get(container)
    if (observerContext) {
        observerContext.cleanup()
        observerMap.delete(container)
    }
}