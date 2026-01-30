import { die } from '../../../utility/utility.js'
import { openTableModal } from './table.js'
import { selectedUsers } from '../select.js'
import { saveAddWorkers } from './save.js'
import { handleException } from '../../../utility/handle-exception.js'

const projectContainer = document.querySelector('.project-container')
const thisProjectId = projectContainer?.dataset.projectid ?? null
if (!thisProjectId || thisProjectId.trim() === '') die('Project ID not found')
    
try {
    const params = new URLSearchParams()
    params.append('status', 'unassigned')
    params.append('role', 'worker')
    const getEndpoint = `users?${params.toString()}`
    openTableModal(getEndpoint, [...selectedUsers])

    const postEndpoint = `projects/${thisProjectId}/workers`
    saveAddWorkers(postEndpoint)
} catch (error) {
    handleException(error)
}
