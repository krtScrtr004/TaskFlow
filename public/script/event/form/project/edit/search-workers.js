import { handleException } from '../../../../utility/handle-exception.js'
import { initializeSearch } from '../worker/search.js'

try {
    const projectId = document.querySelector('#project_form')?.dataset.projectid || ''
    if (!projectId) throw new Error('Project ID not found in project form dataset.')

    const params = new URLSearchParams()
    params.append('status', 'unassigned')
    params.append('role', 'worker')
    params.append('excludeProjectTerminated', 'true')
    params.append('projectReferenceId', projectId)
    params.append('offset', document.querySelector('#workers_section .worker-pool-listing .list').children.length)

    const endpoint = `users?${params.toString()}`
    initializeSearch(endpoint)
} catch (error) {
    handleException(error)
}
