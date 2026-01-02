import { 
    addedPhases, 
    addedWorkers, 
    changedPhases, 
    changedProjectInfo, 
    changedWorkers, 
    removedPhases, 
    removedWorkers 
} from '../record-changes.js'
import { handleException } from '../../../utility/handle-exception.js'
import { Http } from '../../../utility/http.js'
import { debounceAsync } from '../../../utility/debounce.js'
import { Loader } from '../../../render/loader.js'
import { die } from '../../../utility/utility.js'
import { Dialog } from '../../../render/dialog.js'

let isLoading = false

const projectForm = document.querySelector('#project_form')
if (!projectForm) {
    die('Project form not found on the page')
}

const projectId = projectForm.dataset.projectid
if (!projectId) {
    die('Project ID not found')
}

const editProjectButton = document.querySelector('#edit_project_button')
if (!editProjectButton) {
    die('Edit Project button not found in the form')
}

const handler = e => debounceAsync(submit(e), 300)
projectForm.addEventListener('submit', handler)
editProjectButton.addEventListener('click', handler)

/**
 * Submits edited project data to the backend and handles UI feedback.
 *
 * Prevents the default form submission, displays a loading indicator, collects
 * changed project fields and associated phases/workers, formats the payload,
 * sends it to the backend, shows a success dialog and redirects the user to
 * /home after a short delay. Any errors during preparation or submission are
 * forwarded to handleException. The loading indicator is removed in all cases.
 *
 * @async
 * @param {Event} e The submit event (form submission). Default behavior is prevented.
 * @returns {Promise<void>} Resolves when the submit flow completes (either successfully or after error handling).
 */
async function submit(e) {
    e.preventDefault()

    Loader.patch(editProjectButton.querySelector('.text-w-icon'))
    try {
        const projectInfo = Object.fromEntries(changedProjectInfo)
        const phasesInfo = getPhasesData()
        const workersInfo = getWorkersData()

        const formattedData = formatData(projectInfo, phasesInfo, workersInfo)
        await sendToBackend(formattedData)

        // setTimeout(() => window.location.href = `/home`, 1500)
        Dialog.operationSuccess('Project Edited.', 'The project has been successfully edited.')
    } catch (error) {
        handleException(error, `Error preparing project data: ${error.message}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Aggregates phase changes prepared for submission.
 *
 * Reads from the surrounding collections (addedPhases, changedPhases, removedPhases)
 * and builds a plain object containing three arrays:
 *  - toAdd: clones of newly added phase objects
 *  - toEdit: objects containing an `id` and the updated phase properties
 *  - toCancel: ids of phases marked for removal
 *
 * Cloning added phase objects ensures the returned data is detached from the source collections.
 *
 * @returns {{ toAdd: Array<Object>, toEdit: Array<Object>, toCancel: Array<string|number> }}
 *      An object with:
 *        - toAdd: Array of cloned phase objects to create
 *        - toEdit: Array of objects { id, ...phaseData } to update
 *        - toCancel: Array of phase ids to remove
 */
function getPhasesData() {
    const toAdd = []
    const toEdit = []
    const toCancel = []

    for (const [id, value] of addedPhases) {
        toAdd.push({...value})
    }

    for (const [id, value] of changedPhases) {
        toEdit.push({id, ...value})
    }

    for (const id of removedPhases) {
        toCancel.push(id)
    }

    return { toAdd, toEdit, toCancel }
}

/**
 * Constructs structured worker change lists from module-level collections.
 *
 * Iterates over the iterables `addedWorkers`, `changedWorkers`, and `removedWorkers`
 * to produce three arrays:
 *  - toAdd: objects of shape { id, defaultRate } for newly added workers
 *  - toEdit: objects of shape { id, defaultRate } for modified workers
 *  - toRemove: ids for workers to be removed
 *
 * Each entry in `addedWorkers` and `changedWorkers` is expected to be an iterable
 * yielding [id, defaultRate]. This function performs shallow extraction and
 * returns plain arrays suitable for serialization or further processing.
 *
 * @returns {{ toAdd: Array<{id: (string|number), defaultRate: number}>, toEdit: Array<{id: (string|number), defaultRate: number}>, toRemove: Array<(string|number)> }}
 *          An object containing:
 *            - toAdd: array of worker objects to add
 *            - toEdit: array of worker objects to edit
 *            - toRemove: array of worker ids to remove
 */
function getWorkersData() {
    const toAdd = []
    const toEdit = []
    const toRemove = []

    for (const [id, defaultRate] of addedWorkers) {
        toAdd.push({id, defaultRate})
    }

    for (const [id, defaultRate] of changedWorkers) {
        toEdit.push({id, defaultRate})
    }

    for (const id of removedWorkers) {
        toRemove.push(id)
    }
    
    return { toAdd, toEdit, toRemove }
}

/**
 * Formats project, phase, and worker data into the payload expected by the API.
 *
 * This function wraps the provided project info and normalizes the phases and workers
 * collections into objects containing explicit toAdd/toEdit/toCancel (for phases) and
 * toAdd/toEdit/toRemove (for workers) arrays, suitable for submission.
 *
 * @param {Object} info Project info object to be placed under "project"
 * @param {Object} phases Phase collections object with:
 *      - toAdd: Array  Phases to add
 *      - toEdit: Array Phases to edit
 *      - toCancel: Array Phases to cancel
 * @param {Object} workers Worker collections object with:
 *      - toAdd: Array  Workers to add
 *      - toEdit: Array Workers to edit
 *      - toRemove: Array Workers to remove
 *
 * @return {Object} Formatted payload containing:
 *      - project: Object The original project info
 *      - phases: Object { toAdd, toEdit, toCancel }
 *      - workers: Object { toAdd, toEdit, toRemove }
 */
function formatData(info, phases, workers) {
    return {
        'project': info,
        'phases': {
            'toAdd': phases.toAdd,
            'toEdit': phases.toEdit,
            'toCancel': phases.toCancel
        },
        'workers': {
            'toAdd': workers.toAdd,
            'toEdit': workers.toEdit,
            'toRemove': workers.toRemove
        }
    }
}

/**
 * Sends update data for the current project to the backend via a PATCH request.
 *
 * This async helper prevents concurrent requests by checking and setting a shared
 * isLoading flag. It constructs the endpoint using the surrounding scope's `projectId`
 * (as `projects/${projectId}`), delegates the request to `Http.PATCH`, and ensures
 * the isLoading flag is cleared in a finally block.
 *
 * If a request is already in progress the function logs a warning and returns early.
 * If the server returns no response or the request fails, the function rethrows the error.
 *
 * @param {Object} data Partial project data to send in the PATCH request
 * @returns {Promise<void>} Resolves when the request completes; rejects on error
 * @throws {Error} When no response is returned from the server or when Http.PATCH fails
 */
async function sendToBackend(data) {
    if (isLoading) {
        console.warn('Request already in progress. Please wait.')
        return
    }
    isLoading = true

    try {
        const endpoint = `projects/${projectId}`
        const response = await Http.PATCH(endpoint, data)
        if (!response) {
            throw new Error('No response from server')
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}