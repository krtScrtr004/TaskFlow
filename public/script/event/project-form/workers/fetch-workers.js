import { Http } from '../../../utility/http.js'

/**
 * Creates a worker fetcher with an internal single-request guard.
 *
 * The factory returns an async fetchWorkers function that performs an HTTP GET to the
 * provided endpoint using Http.GET, returning response.data on success. While a request
 * is in progress, subsequent calls are ignored and a warning is logged to the console.
 * The internal loading flag is always reset when the request finishes (success or error).
 *
 * @returns {function(string): Promise<any>} A fetchWorkers function:
 *      - endpoint: string URL or endpoint to request worker data from
 *
 * @throws {Error} If no response is returned from the server or if Http.GET rejects.
 *
 * @example
 * const fetchWorkers = createWorkerFetcher();
 * try {
 *     const workers = await fetchWorkers('/api/workers');
 *     // use workers
 * } catch (err) {
 *     // handle error
 * }
 */
export function createWorkerFetcher() {
    let isLoading = false
    
    return async function fetchWorkers(endpoint) {
        if (isLoading) {
            console.warn('Search already in progress. Please wait.')
            return
        }       
        try {
            isLoading = true

            const response = await Http.GET(endpoint)
            if (!response) {
                throw new Error('No response from server!')
            }
            return response.data
        } catch (error) {
            throw error
        } finally {
            isLoading = false
        }
    }
}
