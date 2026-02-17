import { handleException } from '../../../../Utility/HandleException.js'
import { initializeSearch } from '../Worker/Search.js'

try {
    const params = new URLSearchParams()
    params.append('status', 'unassigned')
    params.append('role', 'worker')
    params.append('offset', document.querySelector('#workers_section .worker-pool-listing .list').children.length)

    const endpoint = `users?${params.toString()}`
    initializeSearch(endpoint)
} catch (error) {
    handleException(error)
}