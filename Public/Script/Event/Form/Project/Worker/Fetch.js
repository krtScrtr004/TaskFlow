import { Http } from '../../../../Utility/Http.js'

/**
 * Creates a worker fetcher function bound to an optional default endpoint.
 *
 * The returned async function (fetchWorkers) requests worker data from an endpoint
 * using Http.GET. It prevents concurrent requests via an internal isLoading guard
 * (logs a warning and returns if a fetch is already in progress), optionally appends
 * an offset query parameter using rebuildEndpointWithParams when offset > 1, and
 * returns response.data from the server. If no endpoint is provided, the fetcher
 * returns undefined. If the HTTP call yields no response, the fetcher throws an Error.
 *
 * @param {string|null} [defaultEndpoint=null] Default endpoint URL used when the returned
 *        fetcher is invoked without an override endpoint.
 *
 * @returns {function(string=, {offset?: number}=): Promise<*>} Async fetcher function:
 *      - @param {string} [overrideEndpoint] Optional endpoint to override the default.
 *      - @param {object} [options] Optional options object.
 *           - offset: number Optional page offset (if > 1, appended to endpoint query params).
 *
 * The returned Promise resolves with the server response's data (response.data),
 * resolves with undefined if no endpoint is available or a request is already running,
 * and rejects with an Error if the HTTP call fails or returns no response.
 */
export function createWorkerFetcher(defaultEndpoint = null) {
    let isLoading = false
    
    return async function fetchWorkers(overrideEndpoint,  { offset } = {}) {
        if (isLoading) {
            console.warn('Search already in progress. Please wait.')
            return
        }       
        let endpoint = overrideEndpoint ?? defaultEndpoint ?? ''
        if (!endpoint || endpoint === '') return

        if (!isNaN(offset) && offset > 1)
            endpoint = rebuildEndpointWithParams(endpoint, { offset: offset })

        try {
            isLoading = true

            const response = await Http.GET(endpoint)
            if (!response) throw new Error('No response from server!')

            return response.data
        } catch (error) {
            throw error
        } finally {
            isLoading = false
        }
    }
}

/**
 * Rebuilds an endpoint URL by appending the provided parameters to its query string.
 *
 * The function splits the given endpoint on '?', preserves and parses any existing query
 * parameters, appends each key/value from `params` using URLSearchParams.append (so duplicate
 * keys are allowed and encoded), and returns the path combined with the resulting encoded query.
 *
 * @param {string} endpoint The endpoint URL or path which may already contain a query string.
 * @param {Record<string, string|number|boolean|null|undefined>} [params={}] An object whose own enumerable properties will be appended to the query string. Values are converted to strings by URLSearchParams.
 * @returns {string} The rebuilt endpoint including the encoded query string (i.e. "path?key=val&..."). Note: if no query parameters exist after processing, a trailing '?' will still be present.
 */
export function rebuildEndpointWithParams(endpoint, params = {}) {
    const [path, query = ''] = endpoint.split('?')

    const searchQuery = new URLSearchParams(query)
    for (const [key, value] of Object.entries(params)) {
        searchQuery.append(key, value)
    }

    return `${path}?${searchQuery.toString()}`
}
