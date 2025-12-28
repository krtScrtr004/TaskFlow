import { die } from '../../utility/utility.js'
import { fetchWorkers } from './fetch.js'
import { handleException } from '../../utility/handle-exception.js'
import { renderSelectedWorkerRow } from './render.js'
import { selectedUsers } from './select.js'
import { cleanUp, toggleNoWorkerWall } from './modal.js'
import { Dialog } from '../../render/dialog.js'
import { hideModal } from '../../utility/hide-modal.js'

/**
 * Add Worker Modal (Selection)
 */
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
if (!addWorkerModalTemplate)
    die('Add worker modal wrapper element not found')

const addWorkerModal = addWorkerModalTemplate.querySelector('.add-worker-modal')
if (!addWorkerModal)
    die('Add worker modal not found')

/**
 * Add Worker Table Modal
 */
const addWorkerTableTemplate = document.querySelector('#add_worker_table_template')
if (!addWorkerTableTemplate)
    die('Add worker table wrapper element not found')

const addWorkerTableModal = addWorkerTableTemplate.querySelector('.add-worker-table')
if (!addWorkerTableModal)
    die('Add worker table modal element not found')

const table = addWorkerTableModal.querySelector('table')
if (!table)
    die('Table element not found')

const tableBodySection = table.querySelector('tbody')
if (!tableBodySection)
    throw new Error('Table body element is not found')

export function openTableModal(endpoint,
    {
        key = null,
        offset = 0
    } = {}
) {
    if (!endpoint || endpoint === '')
        throw new Error('Endpoint is required')

    const confirmAddWorkerButton = addWorkerModalTemplate.querySelector('#confirm_add_worker_button')
    if (!confirmAddWorkerButton)
        throw new Error('Confirm add button not found')

    confirmAddWorkerButton.addEventListener('click', async e => {
        e.preventDefault()

        toggleElemDisplay(addWorkerTableTemplate, true)
        try {
            if (selectedUsers.size > 0) {
                endpoint = appendIdsToEndpoint(endpoint, selectedUsers)

                // Fetch worker info
                const workers = await fetchWorkers(endpoint, key, getFetchOffset())
                if (workers?.length < 1) return

                workers?.forEach(worker => tableBodySection.append(renderSelectedWorkerRow(worker)))
            }

            (tableBodySection.childElementCount > 0)
                ? toggleTable(true)
                : toggleTable(false)
        } catch (error) {
            handleException(error, 'Error while fetching user info')
        }

        addButtonEvents()
    })
}

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
        selectedUsers.clear()
    }, { once: true })
}

function getFetchOffset() {
    return addWorkerModal.querySelectorAll('.worker-checkbox') ?? 0
}

function toggleElemDisplay(elem, show, cleanUpElem = null) {
    if (!elem || !(elem instanceof Element))
        throw new Error('Element must be a valid element type')

    if (cleanUpElem && !(cleanUpElem instanceof Element))
        throw new Error('Clean up element must be a valid element')

    if (show) {
        elem.classList.add('flex-col')
        elem.classList.remove('no-display')
    } else {
        elem.classList.add('no-display')
        elem.classList.remove('flex-col')

        if (cleanUpElem) cleanUpElem.textContent = ''
    }
}

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