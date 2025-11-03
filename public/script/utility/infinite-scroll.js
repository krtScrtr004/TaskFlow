import { Loader } from '../render/loader.js'
import { Dialog } from '../render/dialog.js'
import { handleException } from './handle-exception.js'

let isLoading = false
let offset = 0

/**
 * Sets up infinite scrolling on a container element using IntersectionObserver.
 *
 * This function observes a sentinel element within a scrollable container. When the sentinel
 * becomes visible (i.e., the user scrolls near the bottom), it triggers an asynchronous function
 * to load more data, creates DOM elements for the new data, and appends them to the container.
 *
 * @param {HTMLElement} container The scrollable container element where new items will be appended.
 * @param {HTMLElement} sentinel The sentinel element to observe for triggering data loading.
 * @param {Function} asyncFunction An asynchronous function that fetches the next batch of data.
 *        Should return a Promise resolving to the data to be rendered.
 * @param {Function} domCreator A function that takes the fetched data and returns DOM elements to append.
 * @param {number} [existingItemsCount=0] Optional. The number of items already present in the container.
 *        Used to set the initial offset for data fetching.
 *
 * @throws {Error} If any of the required parameters are missing or invalid.
 * @throws {Error} If the IntersectionObserver cannot be created.
 *
 * @example
 * infiniteScroll(
 *   document.getElementById('list'),
 *   document.getElementById('sentinel'),
 *   fetchMoreItems,
 *   createListItem,
 *   20
 * );
 */
export function infiniteScroll(
    container,
    sentinel,
    asyncFunction,
    domCreator,
    existingItemsCount = 0
) {
    if (!container) {
        throw new Error('Container element not found.')
    }

    if (!sentinel) {
        throw new Error('Sentinel element not found.')
    }

    if (typeof asyncFunction !== 'function') {
        throw new Error('asyncFunction must be a function.')
    }

    if (typeof domCreator !== 'function') {
        throw new Error('domCreator must be a function.')
    }

    if (isNaN(existingItemsCount) || existingItemsCount < 0) {
        throw new Error('existingItemsCount must be a non-negative number.')
    }
    offset = existingItemsCount

    try {
        const observer = createObserver(container, asyncFunction, domCreator, offset)
        if (!observer) {
            throw new Error('Failed to create IntersectionObserver.')
        }
        observer.observe(sentinel)
    } catch (error) {
        handleException(error, 'Error in infinite scroll setup:', error)
    }
}

/**
 * Creates an IntersectionObserver for implementing infinite scroll functionality.
 *
 * This function sets up an IntersectionObserver on a container element to detect when a target element
 * becomes visible in the viewport. When triggered, it calls an asynchronous function to fetch more data,
 * uses a DOM creator function to render new items, and manages loading state and loader UI.
 *
 * @param {HTMLElement} container The container element where new DOM elements will be appended and loader is shown.
 * @param {function(number): Promise<Array>} asyncFunction Asynchronous function that fetches data. Receives the current offset as an argument and returns a Promise resolving to an array of items.
 * @param {function(any): void} domCreator Function that creates and appends DOM elements for each item in the fetched data.
 * @param {number} offset The initial offset value used for fetching data. This value is incremented as new data is loaded.
 *
 * @returns {IntersectionObserver} The configured IntersectionObserver instance.
 */
function createObserver(container, asyncFunction, domCreator, offset) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(async (entry) => {
            if (entry.isIntersecting && !isLoading) {
                const el = entry.target

                try {
                    const response = await asyncFunction(offset)
                    if (response?.length < 1) {
                        observer.unobserve(el)
                        return
                    }

                    offset += response.length
                    response.forEach(item => domCreator(item))

                    isLoading = true
                    Loader.trail(container)
                } catch (error) {
                    throw error
                } finally {
                    isLoading = false
                    Loader.delete()
                }
            }
        })
    })
    return observer
}