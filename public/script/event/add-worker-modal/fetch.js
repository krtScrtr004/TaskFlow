import { Http } from '../../utility/http.js'

let isFetchingWorkers = false

/**
 * Fetches workers from the API with optional search and pagination.
 * 
 * @param {string} endpoint - The API endpoint to call
 * @param {string|null} key - Search key for filtering workers
 * @param {number} offset - Pagination offset
 * @returns {Promise<Array>} Array of worker objects
 * @throws {Error} If the request fails or data is not found
 */
export async function fetchWorkers(endpoint, key = null, offset = 0) {
    return await fetchFromDatabase(endpoint, key, offset)
        .catch(error => {
            throw error
        })
}

/** 
 * Internal function to perform the actual HTTP request.
 * Prevents duplicate requests by checking isFetchingWorkers flag.
 */
async function fetchFromDatabase(endpoint, key = null, offset) {
    try {
        if (isFetchingWorkers) {
            console.warn('Request already in progress. Please wait.')
            return false
        }
        isFetchingWorkers = true

        const [path, queryString] = endpoint.split('?')
        const params = new URLSearchParams(queryString)

        params.append('key', key || '')
        params.append('offset', offset)

        const response = await Http.GET(`${path}?${params.toString()}`)
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
