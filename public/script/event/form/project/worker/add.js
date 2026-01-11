import { Notification } from '../../../../render/notification.js'
import { handleException } from '../../../../utility/handle-exception.js'
import { die } from '../../../../utility/utility.js'
import { addedWorkers, removedWorkers } from '../record-changes.js'
import { render } from './render.js'

const existingWorkers = new Set()

const workersSection = document.querySelector('#workers_section')

const selectedWorkersTable = workersSection.querySelector('.selected-workers-table')
if (!selectedWorkersTable) {
    die('Selected workers table not found in workers section.')
}

const noWorkersWall = selectedWorkersTable.querySelector('.no-workers-wall')

const workerPoolListingList = workersSection.querySelector('.worker-pool-listing .list')
if (!workerPoolListingList) {
    die('Worker pool listing list not found.')
}

const selectedWorkersTableList = selectedWorkersTable.querySelector('tbody')
if (!selectedWorkersTableList) {
    die('Selected workers table list not found.')
}

const existingSelectedWorkers = selectedWorkersTableList.querySelectorAll('.selected-worker-row')
existingSelectedWorkers.forEach(row => {
    const workerId = row.dataset.workerid
    if (workerId) {
        existingWorkers.add(workerId)
    }
})

workerPoolListingList.addEventListener('click', e => {
    const card = e.target.closest('.worker-pool-card')
    if (!card) {
        return
    }
    e.stopImmediatePropagation()

    const maxWorkers = document.querySelector('#project_form input[name="max_workers"]')?.value
    if (addedWorkers.size >= parseInt(maxWorkers, 10)) {
        Notification.error(`Cannot add more than ${maxWorkers} workers to the project.`, 5000)
        return
    }

    try {
        const workerId = card.dataset.workerid
        if (existingWorkers.has(workerId) || addedWorkers.has(workerId)) {
            Notification.error('Worker already added to the project.', 3000)
            return
        }
        addedWorkers.set(workerId, 0) // Mark this worker as added
        card.classList.add('selected')

        toggleNoWorkersWall(false)

        // Render and append the new selected worker row
        const newRow = render({
            name: card.querySelector('.worker-info .name')?.textContent || '',
            id: workerId
        })
        selectedWorkersTableList.appendChild(newRow)
    } catch (error) {
        handleException(error, `Error handling worker pool listing click.`)
    }
})

selectedWorkersTableList.addEventListener('click', e => {
    const removeWorkerButton = e.target.closest('.remove-worker-button')
    if (!removeWorkerButton) {
        return
    }
    e.stopImmediatePropagation()
    
    const row = removeWorkerButton.closest('.selected-worker-row')
    if (!row) {
        return
    }

    const workerId = row.dataset.workerid
    addedWorkers.delete(workerId) 
    existingWorkers.delete(workerId)
    if (existingWorkers.has(workerId) || !addedWorkers.has(workerId)) {
        removedWorkers.add(workerId) 
    }

    row.remove()

    const remaining = selectedWorkersTableList?.querySelectorAll('.selected-worker-row') ?? []
    if (remaining.length === 0) {
        toggleNoWorkersWall(true)
    }
})

/**
 * Toggles visibility and layout classes on the noWorkersWall element.
 *
 * When `toggle` is truthy, the function makes the element visible with a column
 * layout by adding the 'flex-col' class and removing 'no-display'. When
 * `toggle` is falsy, the function hides the element by adding 'no-display'
 * and removing 'flex-col'.
 *
 * The function uses optional chaining and will be a no-op if `noWorkersWall`
 * is `null` or `undefined`.
 *
 * @param {boolean} toggle Whether to show (`true`) or hide (`false`) the noWorkersWall element.
 * @returns {void}
 */
function toggleNoWorkersWall(toggle) {
    if (toggle) {
        noWorkersWall?.classList.add('flex-col')
        noWorkersWall?.classList.remove('no-display')
    } else {
        noWorkersWall?.classList.add('no-display')
        noWorkersWall?.classList.remove('flex-col')
    }
}