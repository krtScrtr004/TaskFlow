import { Notification } from '../../../render/notification.js'
import { handleException } from '../../../utility/handle-exception.js'
import { die } from '../../../utility/utility.js'

export const addedWorkers = new Set()

const workersSection = document.querySelector('#workers_section')
const noWorkersWall = workersSection.querySelector('.selected-workers-table  .no-workers-wall')

const workerPoolListingList = workersSection.querySelector('.worker-pool-listing .list')
if (!workerPoolListingList) {
    die('Worker pool listing list not found.')
}

const selectedWorkersTableList = workersSection.querySelector('.selected-workers-table tbody')
if (!selectedWorkersTableList) {
    die('Selected workers table list not found.')
}

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
        if (addedWorkers.has(workerId)) {
            Notification.error('Worker already added to the project.', 3000)
            return
        }
        addedWorkers.add(workerId) // Mark this worker as added
        card.classList.add('selected')

        toggleNoWorkersWall(false)

        // Render and append the new selected worker row
        const newRow = renderSelectedWorkerRow({
            name: card.querySelector('.worker-info .name')?.textContent || '',
            jobTitles: Array.from(card.querySelectorAll('.worker-info .role-chip')).map(el => el.textContent || ''),
            id: workerId
        })
        selectedWorkersTableList.appendChild(newRow)
    } catch (error) {
        handleException(error, `Error handling worker pool listing click.`)
    }
})

/**
 * Renders a table row for a selected worker.
 *
 * This function creates and returns an HTMLTableRowElement (<tr>) that represents
 * a selected worker in the "selected workers" table. The row contains:
 *  - a name cell with a paragraph (.name.multi-line-ellipsis-2),
 *  - a roles cell with zero or more role chips (.role-chip.badge) created from worker.jobTitles,
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
 *  - Clicking the remove button adds a "fade-out" class and waits for the animationend event, then:
 *      - removes the row from the DOM,
 *      - removes the worker id from the global addedWorkers set,
 *      - removes the 'selected' class from the corresponding worker-pool-card in workerPoolListingList (if present),
 *      - and, if no selected rows remain, invokes toggleNoWorkersWall(true).
 *  - When rendering, if a global noWorkersWall element exists, it will be hidden (adds 'hidden' class).
 *  - The function references several globals: BUDGET_MAX, addedWorkers, workerPoolListingList,
 *    selectedWorkersTableList, noWorkersWall, toggleNoWorkersWall. The icon src uses a constant ICON_PATH
 *    inside the function and falls back to an empty string if missing.
 *
 * @param {Object} worker Worker data used to populate the row
 * @param {number|string} [worker.id] Unique identifier for the worker (used for dataset and set membership)
 * @param {string} [worker.name] Display name for the worker
 * @param {Array<string>} [worker.jobTitles] Array of job title strings rendered as role chips
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

    // Roles cell
    const tdRoles = document.createElement('td')
    const rolesWrapper = document.createElement('div')
    rolesWrapper.className = 'roles flex-row flex-wrap'
    const jobTitles = Array.isArray(worker.jobTitles) ? worker.jobTitles : []
    jobTitles.forEach(title => {
        const span = document.createElement('span')
        span.className = 'role-chip badge'
        span.textContent = title
        rolesWrapper.appendChild(span)
    })
    tdRoles.appendChild(rolesWrapper)

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
    btn.className = 'unset-button'
    const img = document.createElement('img')
    img.src = (typeof ICON_PATH !== 'undefined') ? ICON_PATH + 'delete_r.svg' : ''
    img.alt = 'Remove Worker'
    img.title = 'Remove Worker'
    img.height = 24
    btn.appendChild(img)
    center.appendChild(btn)
    tdRemove.appendChild(center)

    tr.appendChild(tdName)
    tr.appendChild(tdRoles)
    tr.appendChild(tdRate)
    tr.appendChild(tdRemove)

    // Hook up remove behavior
    btn.addEventListener('click', () => {
        const card = workerPoolListingList.querySelector(`.worker-pool-card[data-workerid="${worker.id}"]`)
        card?.classList.remove('selected')

        tr.classList.add('fade-out')
        tr.addEventListener('animationend', () => {
            tr.remove()
            addedWorkers.delete(String(worker.id))

            const remaining = selectedWorkersTableList?.querySelectorAll('.selected-worker-row') ?? []
            if (remaining.length === 0) {
                toggleNoWorkersWall(true)
            }
        }, { once: true })
    })

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