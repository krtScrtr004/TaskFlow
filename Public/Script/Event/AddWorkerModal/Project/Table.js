import { die, toggleElementClass } from '../../../Utility/Utility.js'
import { fetchWorkers } from '../Fetch.js'
import { handleException } from '../../../Utility/HandleException.js'
import { renderSelectedWorkerRow } from '../Render.js'
import { selectedUsers } from '../Select.js'
import { cleanUp, toggleNoWorkerWall } from '../Modal.js'
import { hideModal } from '../../../Utility/HideModal.js'
import { debounceAsync } from '../../../Utility/Debounce.js'

/**
 * Add Worker Modal (Selection)
 */
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
if (!addWorkerModalTemplate) die('Add worker modal wrapper element not found')

const addWorkerModal = addWorkerModalTemplate.querySelector('.add-worker-modal')
if (!addWorkerModal) die('Add worker modal not found')

/**
 * Add Worker Table Modal
 */
const addWorkerTableTemplate = document.querySelector('#add_worker_table_template')
if (!addWorkerTableTemplate) die('Add worker table wrapper element not found')

const addWorkerTableModal = addWorkerTableTemplate.querySelector('.add-worker-table')
if (!addWorkerTableModal) die('Add worker table modal element not found')

const table = addWorkerTableModal.querySelector('table')
if (!table) die('Table element not found')

const tableBodySection = table.querySelector('tbody')
if (!tableBodySection) die('Table body element is not found')

const confirmAddWorkerButton = addWorkerModalTemplate.querySelector('#confirm_add_worker_button')
if (!confirmAddWorkerButton) throw new Error('Confirm add button not found')

/**
 * Attaches a debounced click handler to the confirmAddWorkerButton to open/configure the "add worker" table modal.
 *
 * This function ensures an endpoint is provided (throws if missing) and registers a 'click' event listener
 * that calls debounceAsync(...) to invoke confirmAddWorkerButtonEvent with the click Event and the provided endpoint,
 * using a 300ms debounce interval.
 *
 * @param {string} endpoint API endpoint URL used by the confirmAddWorkerButtonEvent handler
 * @throws {Error} If endpoint is null, undefined, not a string, or an empty string
 * @returns {void}
 */
export function openTableModal(endpoint) {
    if (!endpoint || !typeof endpoint === 'string' || endpoint === '')
        throw new Error('Endpoint is required')

    const handler = e => debounceAsync(confirmAddWorkerButtonEvent(e, endpoint), 300)
    confirmAddWorkerButton.addEventListener('click', handler)
}

/**
 * Handle confirmation of adding selected workers and populate the add-worker modal table.
 *
 * Prevents the default action on the event, ensures the add-worker table template is shown,
 * and if there are selected users appends their IDs to the provided endpoint, fetches worker
 * details, and renders each worker as a row into the table body. After processing it toggles
 * the table visibility based on whether any rows exist and registers button event handlers.
 *
 * This function catches and delegates any exceptions to handleException() rather than throwing.
 *
 * @param {Event} e The event that triggered confirmation (e.g., click or submit). Default action will be prevented.
 * @param {string} endpoint The base endpoint/URL to fetch worker data from. May be modified by appendIdsToEndpoint() when selected users exist.
 *
 * @returns {Promise<void>} Resolves when UI has been updated and button events have been bound.
 *
 */
async function confirmAddWorkerButtonEvent(e, endpoint) {
    e.preventDefault()

    toggleElemDisplay(addWorkerTableTemplate, true)

    try {
        if (selectedUsers.size > 0) {
            endpoint = appendIdsToEndpoint(endpoint, selectedUsers)

            // Fetch worker info
            const workers = await fetchWorkers(endpoint, null, getFetchOffset())
            if (workers?.length < 1) return

            workers?.forEach(worker => tableBodySection.append(renderSelectedWorkerRow(worker)))
        }
    } catch (error) {
        handleException(error)
    }

    tableBodySection.childElementCount > 0
        ? toggleTable(true)
        : toggleTable(false)

    addButtonEvents()
}

/**
 * Appends a comma-separated list of selected worker ids to the given endpoint as the `ids` query parameter.
 *
 * This function validates the inputs, serializes the provided Set of ids into a single comma-separated
 * string (preserves Set insertion order; note that the current implementation leaves a trailing comma),
 * merges it with any existing query string on the endpoint, and returns the resulting URL. The ids are
 * added as a single `ids` parameter value (not as repeated parameters).
 *
 * @param {string} endpoint The endpoint or URL to append ids to. Must be a non-empty string and may include an existing query string.
 * @param {Set<number|string>} selectedWorkerId A Set containing worker ids to include. Must be a Set (elements typically numbers or strings).
 *
 * @returns {string} The resulting URL with the `ids` query parameter appended. Existing query parameters are preserved and URL-encoded as needed.
 *
 * @throws {Error} If `endpoint` is missing/invalid or `selectedWorkerId` is not a Set.
 */
function appendIdsToEndpoint(endpoint, selectedWorkerId) {
    if (!endpoint || endpoint === '' || typeof endpoint !== 'string')
        throw new Error('Endpoint is required')

    if (!selectedWorkerId || !(selectedWorkerId instanceof Set))
        throw new Error('Selected worker id must be a set')

    let idsSearchParams = ''
    selectedWorkerId.forEach(id => {
        idsSearchParams += `${id},`
    })

    const [path, query = ''] = endpoint.split('?')

    const searchQuery = new URLSearchParams(query)
    searchQuery.append('ids', idsSearchParams)

    return `${path}?${searchQuery.toString()}`
}

/**
 * Initializes button event handlers for the "Add Worker" table modal.
 *
 * Sets up the modal close behavior and the "Add more" button:
 *  - Attaches a close handler via hideModal(addWorkerTableTemplate).create(...), which calls cleanUp()
 *    and hides the worker table (toggleTable(false)).
 *  - Adds a one-time click listener to the "#add_more_worker_button" that shows the add-worker modal
 *    template, hides the table template, and hides the table view. The listener is attached with the
 *    `{ once: true }` option. Optional chaining is used so no listener is attached if the button is absent.
 *
 * This function relies on the following globals/helpers: addWorkerTableTemplate, addWorkerTableModal,
 * addWorkerModalTemplate, hideModal, cleanUp, toggleElemDisplay, and toggleTable.
 *
 * @returns {void}
 */
function addButtonEvents() {
    // Close button event
    hideModal(addWorkerTableTemplate)
        .create(addWorkerTableModal.querySelector('.close-button'), () => {
            cleanUp()
            toggleTable(false)
        })

    // Add more event
    const addMoreButton = addWorkerTableModal.querySelector('#add_more_worker_button')
    addMoreButton?.addEventListener('click', e => {
        toggleElemDisplay(addWorkerModalTemplate, true)
        toggleElemDisplay(addWorkerTableTemplate, false)
        toggleTable(false)
        // selectedUsers.clear()
    }, { once: true })
}

/**
 * Retrieves all worker checkbox elements within the add-worker modal.
 *
 * Returns a NodeList of elements matching the selector '.worker-checkbox'
 * scoped to the global addWorkerModal container. If the modal container is
 * not present or querySelectorAll yields null/undefined, this function
 * returns 0.
 *
 * @return {NodeList|number} NodeList of matching elements (possibly empty) or 0 when addWorkerModal is unavailable
 */
function getFetchOffset() {
    return addWorkerModal.querySelectorAll('.worker-checkbox') ?? 0
}

/**
 * Toggle an element's display by adding/removing utility classes and optionally clear another element.
 *
 * This function requires a valid DOM Element for `elem`. When `show` is true it makes `elem`
 * visible by adding the 'flex-col' class and removing 'no-display'. When `show` is false it hides
 * `elem` by adding 'no-display' and removing 'flex-col'. If `cleanUpElem` is provided while hiding,
 * its `textContent` will be cleared.
 *
 * @param {Element} elem The target DOM element to show or hide.
 * @param {boolean} show Whether to show (true) or hide (false) the element.
 * @param {Element|null} [cleanUpElem=null] Optional element whose textContent will be cleared when hiding.
 *
 * @throws {Error} If `elem` is not a valid Element or if `cleanUpElem` is provided and is not a valid Element.
 *
 * @returns {void}
 */
function toggleElemDisplay(elem, show, cleanUpElem = null) {
    if (!elem || !(elem instanceof Element))
        throw new Error('Element must be a valid element type')

    if (cleanUpElem && !(cleanUpElem instanceof Element))
        throw new Error('Clean up element must be a valid element')

    if (show) {
        toggleElementClass(elem, ['flex-col'], ['no-display'])
    } else {
        toggleElementClass(elem, ['no-display'], ['flex-col'] )
        if (cleanUpElem) cleanUpElem.textContent = ''
    }
}

/**
 * Toggle the visibility of the worker table inside the "Add Worker" modal.
 *
 * When `show` is true, the function removes the 'no-display' class from the
 * `table` element and disables the "no workers" wall for the add-worker modal.
 * When `show` is false, it adds the 'no-display' class to `table`, clears the
 * `tableBodySection` content, and enables the "no workers" wall for the
 * add-worker modal.
 *
 * This function mutates DOM state and delegates showing/hiding the no-worker
 * wall to `toggleNoWorkerWall(addWorkerTableModal)`. It expects the following
 * global references to be available: `table`, `tableBodySection`,
 * `addWorkerTableModal`, and `toggleNoWorkerWall`.
 *
 * @param {boolean} show Whether to show the table (true) or hide it (false)
 * @returns {void}
 */
function toggleTable(show) {
    if (show) {
        table.classList.remove('no-display')
        toggleNoWorkerWall(false, addWorkerTableModal)
    } else {
        table.classList.add('no-display')
        tableBodySection.textContent = ''
        toggleNoWorkerWall(true, addWorkerTableModal)
    }
}