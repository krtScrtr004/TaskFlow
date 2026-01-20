import { die, toggleElementClass } from '../../../utility/utility.js'
import { selectedUsers } from '../../add-worker-modal/select.js'
import { addedWorkerInfo, changedWorkerInfo, oldWorkerInfo, removedWorkerInfo } from './record-changes.js'

const taskFormMainPage = document.querySelector('.task-form.main-page')

const workerInfo = taskFormMainPage?.querySelector('#worker_info')
if (!workerInfo) die('Worker info element not found')

const selectedWorkerList = workerInfo.querySelector('.selected-worker-list')
if (!selectedWorkerList) die('Selected worker list not found')

selectedWorkerList.addEventListener('click', e => {
    const card = e.target.closest('.selected-task-worker-form-card')
    if (!card) return

    e.stopImmediatePropagation()
    e.preventDefault()

    const removeButton = card.querySelector('.remove-worker-button')
    if (!removeButton) return

    if (e.target === removeButton || removeButton.contains(e.target)) {
        const workerId = card.dataset.workerid
        if (!workerId) die('Worker ID not found on card')

        card.remove() // Remove card from DOM

        // Remove worker info
        if (!addedWorkerInfo.has(workerId)) {
            const unitRateInput = card.querySelector('.unit-rate-input')
            const hoursAssignedInput = card.querySelector('.hours-assigned-input')
            removedWorkerInfo.set(workerId, {
                unitRate: parseFloat(unitRateInput.value),
                estimatedHour: parseFloat(hoursAssignedInput.value)
            })
        }
        addedWorkerInfo.delete(workerId)
        changedWorkerInfo.delete(workerId)
        selectedUsers.delete(workerId)

        // Show no workers wall when no card remains
        const remainingCards = workerInfo.querySelectorAll('.selected-task-worker-form-card')
        if (remainingCards.length <= 0) {
            const noWorkersWall = workerInfo.querySelector('.no-workers-wall')
            toggleElementClass(noWorkersWall, ['flex-col', 'fade-in'], ['no-display'])
        }
    }
})
