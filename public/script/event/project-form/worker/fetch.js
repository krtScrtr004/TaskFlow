import { Http } from '../../../utility/http.js'

/**
 * Creates a worker fetcher function bound to a default endpoint and options.
 *
 * The returned async function, fetchWorkers(overrideEndpoint), performs an HTTP GET
 * to the effective endpoint (overrideEndpoint || defaultEndpoint). If an offset
 * option is provided it is appended as an 'offset' query parameter via
 * rebuildEndpointWithSearchTerm. Concurrent calls are guarded by an internal
 * isLoading flag — subsequent calls while a request is in progress will be
 * ignored and a warning logged. On success the fetcher resolves with response.data.
 * Errors from the HTTP call or missing responses are propagated to the caller.
 *
 * @param {string|null} [defaultEndpoint=null] Default endpoint URL used when an override is not provided.
 * @param {Object} [options] Configuration options.
 * @param {number|string} [options.offset] Optional offset value to include as the 'offset' query parameter.
 * @returns {function(string=): Promise<any>} Async fetcher function that accepts an optional overrideEndpoint and returns the server response data.
 * @throws {Error} If no response is returned from the server or if the underlying HTTP request fails.
 */
export function createWorkerFetcher(defaultEndpoint = null, { offset } = options) {
    let isLoading = false
    
    return async function fetchWorkers(overrideEndpoint) {
        if (isLoading) {
            console.warn('Search already in progress. Please wait.')
            return
        }       
        endpoint = overrideEndpoint ?? defaultEndpoint ?? ''
        if (!endpoint || endpoint === '') return

        if (offset) {
            const params = new URLSearchParams()
            params.append('offset', offset)
            endpoint = rebuildEndpointWithSearchTerm(endpoint, params)
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

/**
 * Rebuilds an endpoint URL by adding, updating, or removing the "key" query parameter.
 *
 * This function parses the query portion of baseEndpoint (if any), preserves all
 * existing query parameters, and then ensures the "key" parameter reflects the
 * provided term:
 *      - If term is a non-empty value, "key" is set to that value.
 *      - If term is falsy (undefined, null, empty string, etc.), the "key" parameter
 *        is removed from the query string.
 *
 * The function returns the base path followed by '?' and the serialized query string.
 * Note: if baseEndpoint contains a fragment (#) after the query string it will be
 * treated as part of the query and percent-encoded; if no query parameters remain,
 * the returned string will include a trailing '?'.
 *
 * @param {string} baseEndpoint The original endpoint URL (may include an existing query string)
 * @param {string} [term] Search term to set as the 'key' parameter; falsy values remove 'key'
 * @return {string} The reconstructed endpoint with the updated query string
 */
export function rebuildEndpointWithSearchTerm(baseEndpoint, term) {
    const params = new URLSearchParams(baseEndpoint.split('?')[1] || '')
    if (term) {
        params.set('key', term)
    } else {
        params.delete('key')
    }
    return `${baseEndpoint.split('?')[0]}?${params.toString()}`
}
