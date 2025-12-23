import { Notification } from '../../../render/notification.js'
import { handleException } from '../../../utility/handle-exception.js'
import { die } from '../../../utility/utility.js'

export const addedWorkers = new Set()
export const removedWorkers = new Set()
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
        addedWorkers.add(workerId) // Mark this worker as added
        card.classList.add('selected')

        toggleNoWorkersWall(false)

        // Render and append the new selected worker row
        const newRow = renderSelectedWorkerRow({
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
    addedWorkers.delete(workerId) // Remove from added workers set, if exists
    removedWorkers.add(workerId) // Mark as removed if it was an existing worker
    existingWorkers.delete(workerId) // Remove from existing workers set, if exists
    row.remove()

    const remaining = selectedWorkersTableList?.querySelectorAll('.selected-worker-row') ?? []
    if (remaining.length === 0) {
        toggleNoWorkersWall(true)
    }
})

/**
 * Renders a table row for a selected worker.
 *
 * This function creates and returns an HTMLTableRowElement (<tr>) that represents
 * a selected worker in the "selected workers" table. The row contains:
 *  - a name cell with a paragraph (.name.multi-line-ellipsis-2),
 *  - a rate cell with a prefixed currency input (₱) that formats the provided worker.rate to two decimals
 *    (defaults to "500.00" when rate is not provided), and
 *  - a remove cell with a button containing a delete icon.
 *
 * Side effects and behavior:
 *  - Throws an Error if the worker argument is falsy.
 *  - The returned <tr> has classes "selected-worker-row fade-in" and its dataset.workerid is set to the
 *    stringified worker.id (or an empty string when missing).
 *  - The rate <input> is numeric, min="0", step="0.01", required, and will set a max attribute when
 *    the global BUDGET_MAX is defined.
 *  - When rendering, if a global noWorkersWall element exists, it will be hidden (adds 'hidden' class).
 *  - The function references several globals: BUDGET_MAX, addedWorkers, workerPoolListingList,
 *    selectedWorkersTableList, noWorkersWall, toggleNoWorkersWall. The icon src uses a constant ICON_PATH
 *    inside the function and falls back to an empty string if missing.
 *
 * @param {Object} worker Worker data used to populate the row
 * @param {number|string} [worker.id] Unique identifier for the worker (used for dataset and set membership)
 * @param {string} [worker.name] Display name for the worker
 * @param {number|string} [worker.rate] Default rate for the worker; formatted to two decimal places in the input
 *
 * @returns {HTMLTableRowElement} The fully constructed <tr> element representing the selected worker
 *
 * @throws {Error} If the worker argument is not provided or is falsy
 */
function renderSelectedWorkerRow(worker) {
    if (!worker) {
        throw new Error('Worker data is required to render a selected worker row')
    }
    const ICON_PATH = '/public/asset/image/icon/'

    const tr = document.createElement('tr')
    tr.className = 'selected-worker-row fade-in'
    tr.dataset.workerid = String(worker.id || '')

    // Name cell
    const tdName = document.createElement('td')
    const pName = document.createElement('p')
    pName.className = 'name multi-line-ellipsis-2'
    pName.textContent = worker.name || ''
    tdName.appendChild(pName)

    // Rate cell (with prefix)
    const tdRate = document.createElement('td')
    const inputWrap = document.createElement('div')
    inputWrap.className = 'input-w-prefix'
    const prefix = document.createElement('span')
    prefix.className = 'input-prefix'
    prefix.textContent = '₱'
    const input = document.createElement('input')
    input.type = 'number'
    input.className = 'default-rate-input'
    input.value = (typeof worker.rate !== 'undefined') ? Number(worker.rate).toFixed(2) : '500.00'
    input.min = '0'
    if (typeof BUDGET_MAX !== 'undefined') input.max = String(BUDGET_MAX)
    input.step = '0.01'
    input.required = true
    inputWrap.appendChild(prefix)
    inputWrap.appendChild(input)
    tdRate.appendChild(inputWrap)

    // Remove button cell
    const tdRemove = document.createElement('td')
    const center = document.createElement('span')
    center.className = 'center-child'
    const btn = document.createElement('button')
    btn.type = 'button'
    btn.className = 'remove-worker-button unset-button'
    const img = document.createElement('img')
    img.src = (typeof ICON_PATH !== 'undefined') ? ICON_PATH + 'delete_r.svg' : ''
    img.alt = 'Remove Worker'
    img.title = 'Remove Worker'
    img.height = 24
    btn.appendChild(img)
    center.appendChild(btn)
    tdRemove.appendChild(center)

    tr.appendChild(tdName)
    tr.appendChild(tdRate)
    tr.appendChild(tdRemove)

    // When rendering a row, hide the "no workers" wall if present
    if (noWorkersWall) {
        noWorkersWall.classList.add('hidden')
    }

    return tr
}

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