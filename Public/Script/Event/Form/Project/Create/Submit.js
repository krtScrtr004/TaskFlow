import { handleException } from '../../../../Utility/HandleException.js'
import { Http } from '../../../../Utility/Http.js'
import { debounceAsync } from '../../../../Utility/Debounce.js'
import { Dialog } from '../../../../Render/Dialog.js'
import { Loader } from '../../../../Render/Loader.js'
import { die } from '../../../../Utility/Utility.js'
import { 
    getMergedAddedAndChangedPhasesMap, 
    getMergedAddedAndChangedProjectsMap, 
    getMergedAddedAndChangedWorkersMap 
} from '../RecordChanges.js'

let isLoading = false

const projectForm = document.querySelector('#project_form')
if (!projectForm) die('Project form not found on the page')

const createProjectButton = document.querySelector('#create_project_button')
if (!createProjectButton) die('Create Project button not found in the form')

const handler = e => debounceAsync(submit(e), 300)
projectForm.addEventListener('submit', handler)
createProjectButton.addEventListener('click', handler)

/**
 * Handles the project creation form submission.
 *
 * This async function prevents the default form submission behavior, displays a loading
 * indicator, collects project data from various form sections (project info, phases, and workers),
 * formats the data, and sends it to the backend. Upon successful creation, redirects the user
 * to the home page after a brief delay and displays a success dialog.
 *
 * @param {Event} e The form submission event object
 *
 * @returns {Promise<void>} Resolves when the submission process completes
 *
 * @throws {Error} Catches and handles any errors during data preparation or submission,
 *      displaying an error message via handleException
 */
async function submit(e) {
    e.preventDefault()

    Loader.patch(createProjectButton.querySelector('.text-w-icon'))
    try {
        const projectInfo = getProjectData()
        const phasesInfo = getPhasesData()
        const workersInfo = getWorkersData()

        const formattedData = formatData(projectInfo, phasesInfo, workersInfo)
        await sendToBackend(formattedData)

        setTimeout(() => window.location.href = `/home`, 1500)
        Dialog.operationSuccess('Project Created.', 'The project has been successfully created.')
    } catch (error) {
        handleException(error)
    } finally {
        Loader.delete()
    }
}

/**
 * Constructs a plain object containing project fields by reading values from the
 * Map returned by getMergedAddedAndChangedProjectsMap().
 *
 * The function extracts the following keys from the merged Map:
 *  - 'name'
 *  - 'description'
 *  - 'budget'
 *  - 'maxWorkers'
 *  - 'startDateTime'
 *  - 'completionDateTime'
 *
 * @returns {Object} Project data object
 * @property {(string|null)} name Human-readable project name
 * @property {(string|null)} description Project description or notes
 * @property {(number|null)} budget Numeric budget value (currency units)
 * @property {(number|null)} maxWorkers Maximum allowed workers for the project
 * @property {(string|null)} startDateTime ISO-8601 start date/time string, if available
 * @property {(string|null)} completionDateTime ISO-8601 completion date/time string, if available
 */
function getProjectData() {
    const merged = getMergedAddedAndChangedProjectsMap()

    const name = merged.get('name')
    const description = merged.get('description')
    const budget = merged.get('budget')
    const maxWorkers = merged.get('maxWorkers')
    const startDateTime = merged.get('startDateTime')
    const completionDateTime = merged.get('completionDateTime')

    return {
        name,
        description,
        budget,
        maxWorkers,
        startDateTime,
        completionDateTime
    }
}

/**
 * Collects phase records from the merged map of added and changed phases.
 *
 * This function retrieves the Map returned by getMergedAddedAndChangedPhasesMap(),
 * extracts each value (phase object) preserving the Map's iteration order, and
 * returns them as an array suitable for submission or further processing.
 *
 * @returns {Array<Object>} Array of phase objects (empty if no phases present)
 */
function getPhasesData() {
    const phasesData = []

    const merged = getMergedAddedAndChangedPhasesMap()
    for (const value of merged.values()) {
        phasesData.push(value)
    }

    return phasesData
}

/**
 * Builds an array of worker data objects from the merged added/changed workers map.
 *
 * This function retrieves a Map via getMergedAddedAndChangedWorkersMap(), iterates
 * over its entries [id, defaultRate], and returns an array where each element is
 * an object with 'id' and 'defaultRate' properties. The resulting array preserves
 * the iteration order of the Map and is suitable for serialization or form submission.
 *
 * @returns {Array<{id: (string|number), defaultRate: number}>} Array of worker objects:
 *      - id: string|number Identifier of the worker (key from the Map)
 *      - defaultRate: number Default rate/value associated with the worker (Map value)
 */
function getWorkersData() {
    const workersData = []

    const merged = getMergedAddedAndChangedWorkersMap()
    for (const [id, defaultRate] of merged) {
        workersData.push({ id, defaultRate })
    }
    return workersData
}

/**
 * Formats project data for submission.
 *
 * This function takes raw project information, phases, and workers data
 * and transforms them into a structured object suitable for API submission
 * or further processing.
 *
 * @param {Object} info Project information object containing:
 *      - name: string Project name
 *      - description: string Project description
 *      - budget: number Project budget
 *      - maxWorkers: number Maximum number of workers allowed
 *      - startDateTime: string|Date Project start date and time
 *      - completionDateTime: string|Date Project completion date and time
 * @param {Array|Iterable} phases Collection of phase objects, each containing:
 *      - name: string Phase name
 *      - description: string Phase description
 *      - budget: number Phase budget
 *      - contingencyRate: number Contingency rate for the phase
 *      - budgetNote: string Additional notes about the budget
 *      - startDateTime: string|Date Phase start date and time
 *      - completionDateTime: string|Date Phase completion date and time
 * @param {Array|Iterable} workers Collection of worker objects, each containing:
 *      - id: number|string Worker identifier
 *      - defaultRate: number Default hourly/daily rate for the worker
 *
 * @return {Object} Formatted project data object ready for submission
 */
function formatData(info, phases, workers) {
    return {
        'project': {
            'name': info.name,
            'description': info.description,
            'budget': info.budget,
            'maxWorkers': info.maxWorkers,
            'startDateTime': info.startDateTime,
            'completionDateTime': info.completionDateTime,
        },
        'phases': Array.from(phases).map(phase => ({
            'name': phase.name,
            'description': phase.description,
            'budget': phase.budget,
            'contingencyRate': phase.contingencyRate,
            'budgetNote': phase.budgetNote,
            'startDateTime': phase.startDateTime,
            'completionDateTime': phase.completionDateTime
        })),
        'workers': Array.from(workers).map(worker => ({
            'id': worker.id,
            'defaultRate': worker.defaultRate
        }))
    }
}

/**
 * Sends project data to the backend API.
 *
 * This function handles the submission of project data to the server,
 * implementing a loading state guard to prevent duplicate requests.
 * It uses the Http.POST method to send data to the project endpoint.
 *
 * @async
 * @param {Object} data The project data to be sent to the backend
 *
 * @returns {Promise<void>} Resolves when the request completes successfully
 *
 * @throws {Error} Throws an error if no response is received from the server
 * @throws {Error} Re-throws any error that occurs during the HTTP request
 */
async function sendToBackend(data) {
    if (isLoading) {
        console.warn('Request already in progress. Please wait.')
        return
    }
    isLoading = true

    try {
        const endpoint = `projects/`
        const response = await Http.POST(endpoint, data)
        if (!response) throw new Error('No response from server')
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}