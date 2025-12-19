import { handleException } from '../../../utility/handle-exception.js'
import { initializeSearch } from '../worker/search.js'

try {
    const params = new URLSearchParams()
    params.append('status', 'unassigned')
    // params.append('excludeProjectTerminated', 'true')

    const endpoint = `workers?${params.toString()}`
    initializeSearch(endpoint)
} catch (error) {
    handleException(error, `Error searching workers: ${error.message}`)
}
