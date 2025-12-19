import { Http } from '../../../utility/http.js'

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
