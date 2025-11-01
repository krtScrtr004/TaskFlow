import { Http } from '../../utility/http.js'

let isFetchingWorkers = false

/**
 * Fetches workers from the API with optional search and pagination.
 * 
 * @param {string} projectId - The project ID to fetch workers for
 * @param {string} endpoint - The API endpoint to call
 * @param {string|null} key - Search key for filtering workers
 * @param {number} offset - Pagination offset
 * @returns {Promise<Array>} Array of worker objects
 * @throws {Error} If the request fails or data is not found
 */
export async function fetchWorkers(projectId, endpoint, key = null, offset = 0) {
    return await fetchFromDatabase(projectId, endpoint, key, offset)
        .catch(error => {
            throw error
        })
}

/**
 * Internal function to perform the actual HTTP request.
 * Prevents duplicate requests by checking isFetchingWorkers flag.
 */
async function fetchFromDatabase(projectId, endpoint, key = null, offset) {
    try {
        if (isFetchingWorkers) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isFetchingWorkers = true

        const param = (key) ? key : ''
        const response = await Http.GET(`${endpoint}&key=${param}&offset=${offset}`)
        if (!response) {
            throw new Error('Workers data not found!')
        }

        isFetchingWorkers = false
        return response.data
    } catch (error) {
        isFetchingWorkers = false
        throw error
    }
}
