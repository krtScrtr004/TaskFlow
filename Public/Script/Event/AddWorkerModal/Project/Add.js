import { die } from '../../../Utility/Utility.js'
import { openTableModal } from './Table.js'
import { selectedUsers } from '../Select.js'
import { saveAddWorkers } from './Save.js'
import { handleException } from '../../../Utility/HandleException.js'

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
