import { handleException } from '../../../utility/handle-exception.js'
import { Http } from '../../../utility/http.js'
import { debounceAsync } from '../../../utility/debounce.js'
import { Dialog } from '../../../render/dialog.js'
import { Loader } from '../../../render/loader.js'
import { die } from '../../../utility/utility.js'

let isLoading = false

const projectForm = document.querySelector('#project_form')
if (!projectForm) {
    die('Project form not found on the page')
}

const createProjectButton = document.querySelector('#create_project_button')
if (!createProjectButton) {
    die('Create Project button not found in the form')
}

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
        handleException(error, `Error preparing project data: ${error.message}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Retrieves project data from the project form.
 *
 * This function extracts all project-related input values from the info section
 * of the project form, including name, description, budget, maximum workers,
 * and date/time fields. All string values are trimmed, and numeric values are
 * parsed to their appropriate types.
 *
 * @throws {Error} Throws an error if the info section is not found in the form
 *
 * @returns {Object} An object containing the project data:
 *      - name: string The project name
 *      - description: string The project description
 *      - budget: number The project budget as a floating-point number
 *      - maxWorkers: number The maximum number of workers as an integer
 *      - startDateTime: string The project start date and time
 *      - completionDateTime: string The project completion date and time
 */
function getProjectData() {
    const infoSection = projectForm.querySelector('#info_section')
    if (!infoSection) {
        throw new Error('Info section not found in the form')
    }

    const name = infoSection.querySelector('input[name="name"]').value.trim()
    const description = infoSection.querySelector('textarea[name="description"]').value.trim()
    const budget = parseFloat(infoSection.querySelector('input[name="budget"]').value.trim())
    const maxWorkers = parseInt(infoSection.querySelector('input[name="max_workers"]').value.trim(), 10)
    const startDateTime = infoSection.querySelector('input[name="start_date_time"]').value.trim()
    const completionDateTime = infoSection.querySelector('input[name="completion_date_time"]').value.trim()

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
 * Retrieves phase data from the project form.
 *
 * This function queries the project form for the phase section and extracts
 * all phase form cards within it. For each card, it collects the phase details
 * including name, description, budget information, and date/time values.
 *
 * @throws {Error} If the phase section element is not found in the form
 *
 * @returns {Array<Object>} An array of phase data objects, each containing:
 *      - name: string The trimmed phase name
 *      - description: string The trimmed phase description
 *      - budget: number The parsed budget value as a float
 *      - contingencyRate: number The parsed contingency rate as a float
 *      - budgetNote: string The trimmed budget note
 *      - startDateTime: string The trimmed start date and time value
 *      - completionDateTime: string The trimmed completion date and time value
 */
function getPhasesData() {
    const phaseSection = projectForm.querySelector('#phase_section')
    if (!phaseSection) {
        throw new Error('Phases section not found in the form')
    }

    const phaseFormCards = phaseSection.querySelectorAll('.phase-form-card')
    const phasesData = []
    phaseFormCards.forEach(card => {
        const name = card.querySelector('input[name="name"]').value.trim()
        const description = card.querySelector('textarea[name="description"]').value.trim()
        const budget = parseFloat(card.querySelector('input[name="budget"]').value.trim())
        const contingencyRate = parseFloat(card.querySelector('input[name="contingency_rate"]').value.trim())
        const budgetNote = card.querySelector('textarea[name="budget_note"]').value.trim()
        const startDateTime = card.querySelector('input[name="start_date_time"]').value.trim()
        const completionDateTime = card.querySelector('input[name="completion_date_time"]').value.trim()

        phasesData.push({
            name,
            description,
            budget,
            contingencyRate,
            budgetNote,
            startDateTime,
            completionDateTime
        })
    })
    return phasesData
}

/**
 * Retrieves worker data from the project form's workers section.
 *
 * This function queries the DOM for selected worker rows within the workers section
 * of the project form and extracts the worker ID and default rate from each row.
 * It validates that the workers section exists before attempting to gather data.
 *
 * @throws {Error} If the workers section element is not found in the form
 *
 * @returns {Array<Object>} An array of worker data objects:
 *      - id: string The worker's unique identifier from the data-workerid attribute
 *      - defaultRate: number The worker's default rate parsed as a float
 */
function getWorkersData() {
    const workersSection = projectForm.querySelector('#workers_section')
    if (!workersSection) {
        throw new Error('Workers section not found in the form')
    }

    const selectedWorkerRows = workersSection.querySelectorAll('.selected-workers-table > table tr.selected-worker-row')
    const workersData = []
    selectedWorkerRows.forEach(row => {
        const id = row.getAttribute('data-workerid')
        const defaultRate = parseFloat(row.querySelector('input.default-rate-input').value.trim())

        workersData.push({ id, defaultRate })
    })
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
        if (!response) {
            throw new Error('No response from server')
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}

