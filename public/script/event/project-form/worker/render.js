import { formatNumber } from '../../../utility/utility.js'

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
export function render(worker) {
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
    input.value = (typeof worker.rate !== 'undefined') ? formatNumber(worker.defaultRate) : '0.00'
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