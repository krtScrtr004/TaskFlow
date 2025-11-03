import { infiniteScroll } from '../../utility/infinite-scroll.js'
import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { createUserGridCard } from './create-user-grid-card.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const userGridContainer = document.querySelector('.user-grid-container')

const userGrid = userGridContainer?.querySelector('.user-grid')
if (!userGrid) {
    console.warn('User Grid element not found.')
}

const sentinel = userGridContainer?.querySelector('.sentinel')
if (!sentinel) {
    console.warn('Sentinel element not found.')
}

const projectId = userGridContainer?.dataset.projectid
if (!projectId || projectId.trim() === '') {
    console.warn('Project ID not found.')
}

try {
    // Initialize infinite scroll for loading users
    infiniteScroll(
        userGrid,
        sentinel,
        (offset) => asyncFunction(offset),
        (user) => createUserGridCard(user),
        getExistingItemsCount()
    )
} catch (error) {
    handleException(error, 'Error initializing infinite scroll:', error)
}

/**
 * Retrieves the current count of user items displayed or specified by the URL offset.
 *
 * This function checks the URL's query parameters for an 'offset' value. If present,
 * it returns the value of 'offset'. If not, it returns the number of elements with the
 * class 'user-grid-card' within the 'userGrid' container.
 *
 * @returns {number|string} The number of existing user items, either from the 'offset'
 *   query parameter or by counting '.user-grid-card' elements in the DOM.
 */
function getExistingItemsCount() {
    const queryParams = new URLSearchParams(window.location.search)
    return queryParams.get('offset') ?? userGrid.querySelectorAll('.user-grid-card').length
}

/**
 * Asynchronously fetches user data with infinite scroll support.
 *
 * This function manages loading state to prevent concurrent requests,
 * validates the provided offset and project ID, and fetches user data
 * from the server using an HTTP GET request. Throws errors for invalid
 * input or failed requests.
 *
 * @param {number} offset The offset value for paginated user data retrieval. Must be a non-negative integer.
 * @throws {Error} If a request is already in progress, the offset is invalid, the project ID is missing, or the server response is invalid.
 * @returns {Promise<any>} Resolves with the user data from the server response.
 */
async function asyncFunction(offset) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!offset || isNaN(offset) || offset < 0) {
            throw new Error('Invalid offset value.')
        }

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID not found.')
        }

        const response = await Http.GET(`users?offset=${offset}`)
        if (!response) {
            throw new Error('No response from server.')
        }

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}