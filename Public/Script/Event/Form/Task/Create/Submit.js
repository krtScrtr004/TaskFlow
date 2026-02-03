import { Dialog } from '../../../../Render/Dialog.js'
import { Loader } from '../../../../Render/Loader.js'
import { debounceAsync } from '../../../../Utility/Debounce.js'
import { handleException } from '../../../../Utility/HandleException.js'
import { Http } from '../../../../Utility/Http.js'
import { die } from '../../../../Utility/Utility.js'
import { getMergedAddedAndChangedTasksMap, getMergedAddedAndChangedWorkersMap } from '../RecordChanges.js'

let isLoading = false

const taskFormMainPage = document.querySelector('.task-form.main-page')

const projectId = taskFormMainPage?.dataset.projectid
if (!projectId) die('Project ID not found')

const phaseId = taskFormMainPage?.dataset.phaseid
if (!phaseId) die('Phase ID not found')

const taskForm = taskFormMainPage?.querySelector('#task_form')
if (!taskForm) die('Task form element not found')

const submitTaskButton = taskForm.querySelector('.submit-task-button')
if (!submitTaskButton) die('Submit task button not found')

const handler = e => debounceAsync(submit(e), 300)
taskForm.addEventListener('submit', handler)
submitTaskButton.addEventListener('click', handler)

/**
 * Handles the submission of a task creation form.
 *
 * This asynchronous function prevents the default form submission behavior, gathers task and worker
 * information, sends the data to the backend, and handles the response. On success, it displays a
 * success dialog and redirects the user to the newly created task's page. On failure, it handles
 * the exception appropriately.
 *
 * Behavior and side effects:
 * - Prevents the default form submission event using e.preventDefault().
 * - Retrieves task information via getTaskInfo() and worker information via getWorkersInfo().
 * - Displays a loading indicator on the submit button using Loader.patch().
 * - Sends a POST request to the backend with combined task and worker data via sendToBackend().
 * - On successful response, displays a success dialog using Dialog.operationSuccess().
 * - Redirects to the task detail page after a 1.5 second delay using window.location.href.
 * - On error, invokes handleException() to process and display the error.
 * - Always removes the loading indicator using Loader.delete() in the finally block.
 * - Relies on external variables: submitTaskButton, projectId, and phaseId.
 *
 * @param {Event} e - The form submission event object
 *
 * @throws {Error} Propagates any errors from getTaskInfo(), getWorkersInfo(), or sendToBackend()
 *                 after handling them via handleException()
 *
 * @return {Promise<void>} A promise that resolves when the submission process completes
 */
async function submit(e) {
    e.preventDefault()

    const taskInfo = getTaskInfo()
    const workersInfo = getWorkersInfo()

    Loader.patch(submitTaskButton.querySelector('.text-w-icon'))
    try {
        const data = await sendToBackend({
            ...taskInfo,
            workers: workersInfo
        })

        Dialog.operationSuccess('Task Added', 'New task has been added')
        setTimeout(() => window.location.href = `project/${projectId}/phase/${phaseId}/task/${data.id}`, 1500) 
    } catch (error) {
        handleException(error)
    } finally {
        Loader.delete()
    }
}

/**
 * Retrieves task information from merged added and changed tasks.
 *
 * This function fetches task-related data from a merged map of added and changed tasks
 * by calling getMergedAddedAndChangedTasksMap(). It extracts specific task properties
 * including name, timestamps, description, cost estimate, and budget notes, then returns
 * them as a structured object.
 *
 * Behavior and side effects:
 * - Calls getMergedAddedAndChangedTasksMap() to obtain the merged task data map.
 * - Retrieves the following properties from the map: 'name', 'startDateTime', 
 *   'completionDateTime', 'description', 'estimatedCost', and 'budgetNote'.
 * - Returns an object containing all extracted properties.
 * - If any property is not present in the map, the corresponding value will be undefined.
 * - Does not modify the merged map or perform validation on the retrieved values.
 * - No side effects on external state beyond the call to getMergedAddedAndChangedTasksMap().
 *
 * @returns {Object} Object containing task information with the following properties:
 *   - {string|undefined} name - The task name
 *   - {string|Date|undefined} startDateTime - The task start date and time
 *   - {string|Date|undefined} completionDateTime - The task completion date and time
 *   - {string|undefined} description - The task description
 *   - {string|undefined} priority - The task priority
 *   - {number|string|undefined} estimatedCost - The estimated cost for the task
 *   - {string|undefined} budgetNote - Additional budget-related notes
 */
function getTaskInfo() {
    const merged = getMergedAddedAndChangedTasksMap()
    const name = merged.get('name')
    const startDateTime = merged.get('startDateTime')
    const completionDateTime = merged.get('completionDateTime')
    const description = merged.get('description')
    const estimatedCost = merged.get('estimatedCost')
    const budgetNote = merged.get('budgetNote')

    // Directly access the priority from the form element
    const priority = taskForm.querySelector('#priority')?.value || '' 

    return {
        name,
        startDateTime,
        completionDateTime,
        description,
        priority,
        estimatedCost,
        budgetNote
    }
}

/**
 * Retrieves information about all workers from the merged added and changed workers map.
 *
 * This function collects worker data by iterating over the merged map of added and changed
 * workers, transforming each entry into an object containing the worker's ID and associated
 * properties. The merged map is obtained via getMergedAddedAndChangedWorkersMap() and contains
 * worker entries that have been either newly added or modified.
 *
 * Behavior and side effects:
 * - Calls getMergedAddedAndChangedWorkersMap() to retrieve a Map of workers.
 * - Iterates over the Map entries, where each entry consists of [id, value] pairs.
 * - Constructs an array of worker objects, each containing the worker ID as a separate property
 *   along with all properties from the value object (spread via ...value).
 * - Does not modify the original merged workers map or any external state.
 * - Returns an empty array if the merged map contains no entries.
 *
 * @return {Array<Object>} Array of worker information objects, each containing an 'id' property
 *                         and all properties from the corresponding worker value object
 */
function getWorkersInfo() {
    const workersData = []
    const merged = getMergedAddedAndChangedWorkersMap()
    for (const [id, value] of merged) {
        workersData.push({ id, ...value })
    }
    return workersData
}

/**
 * Sends task data to the backend API endpoint.
 *
 * This async function submits task creation data to the server using an HTTP POST request.
 * It implements a loading state mechanism to prevent concurrent requests and ensures proper
 * cleanup of the loading state regardless of success or failure.
 *
 * Behavior and side effects:
 * - Checks if a request is already in progress via the isLoading flag and exits early if true.
 * - Sets isLoading to true before initiating the request to prevent duplicate submissions.
 * - Constructs a REST endpoint URL using the provided projectId and phaseId variables from outer scope.
 * - Makes an HTTP POST request to the constructed endpoint with the provided data payload.
 * - Validates that a response was received from the server and throws an error if not.
 * - Logs a warning to the console if called while another request is in progress.
 * - Always resets isLoading to false in the finally block, ensuring cleanup even on errors.
 * - Re-throws any caught errors to allow upstream error handling.
 * - Does not handle the successful response data; calling code must handle promise resolution.
 *
 * @param {Object} data The task data payload to send to the backend API
 *
 * @throws {Error} If no response is received from the server
 * @throws {Error} Any network or HTTP-related errors from the Http.POST method
 *
 * @return {Promise<void>} A promise that resolves when the request completes successfully
 */
async function sendToBackend(data) {
    if (isLoading) {
        console.warn('Request already in progress. Please wait.')
        return
    }
    isLoading = true

    try {
        const endpoint = `projects/${projectId}/phases/${phaseId}/tasks`
        const response = await Http.POST(endpoint, data) 
        if (!response) throw new Error('No response from the server')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}
