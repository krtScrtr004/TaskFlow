import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { debounceAsync } from '../../utility/debounce.js'

let isLoading = false
let isSelectWorkerEventInitialized = false
export const selectedUsers = []
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

// Fetch Worker -------------------------

export async function fetchWorkers(projectId, key = null) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID is required.')

        const param = (key) ? key : ''
        const response = await Http.GET(`projects/${projectId}/workers/${param}`)
        if (!response)
            throw new Error('Workers data not found!')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}

// Create Worker List Card -------------------------

export function createWorkerListCard(worker) {
    const ICON_PATH = 'asset/image/icon/';
    const workerList = addWorkerModalTemplate.querySelector('.worker-list');

    // Create main container div
    const workerCheckbox = document.createElement('div');
    workerCheckbox.className = 'worker-checkbox flex-row flex-child-center-h';

    // Create checkbox input
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.name = worker.id;
    checkbox.id = worker.id;
    workerCheckbox.appendChild(checkbox);

    // Create label
    const label = document.createElement('label');
    label.htmlFor = worker.id;
    label.className = 'worker-list-card';
    label.dataset.id = worker.id;

    // Create image container
    const imgContainer = document.createElement('div');
    imgContainer.className = 'flex-col flex-child-center-v';
    
    const img = document.createElement('img');
    img.src = worker.profilePicture || ICON_PATH + 'profile_w.svg';
    img.alt = worker.name;
    img.title = worker.name;
    img.height = 40;
    imgContainer.appendChild(img);
    
    // Create info container
    const infoContainer = document.createElement('div');
    infoContainer.className = 'flex-col';
    
    // Create name and ID section
    const nameSection = document.createElement('div');
    
    const nameHeader = document.createElement('h4');
    nameHeader.className = 'wrap-text';
    nameHeader.textContent = worker.name;
    nameSection.appendChild(nameHeader);
    
    const idPara = document.createElement('p');
    const idEm = document.createElement('em');
    idEm.textContent = worker.id;
    idPara.appendChild(idEm);
    nameSection.appendChild(idPara);
    
    // Create job titles section
    const jobTitlesDiv = document.createElement('div');
    jobTitlesDiv.className = 'job-titles flex-row flex-wrap';
    
    if (worker.jobTitles && worker.jobTitles.length > 0) {
        worker.jobTitles.forEach(title => {
            const span = document.createElement('span');
            span.className = 'job-title-chip';
            span.textContent = title;
            jobTitlesDiv.appendChild(span);
        });
    } else {
        const noJobSpan = document.createElement('span');
        noJobSpan.className = 'no-job-title-badge';
        noJobSpan.textContent = 'No Job Titles';
        jobTitlesDiv.appendChild(noJobSpan);
    }
    
    // Assemble the components
    infoContainer.appendChild(nameSection);
    infoContainer.appendChild(jobTitlesDiv);
    
    label.appendChild(imgContainer);
    label.appendChild(infoContainer);
    
    workerCheckbox.appendChild(label);
    
    // Add to DOM
    workerList.insertAdjacentElement('afterbegin', workerCheckbox);
}

// Select Worker -------------------------

export function selectWorker() {
    if (isSelectWorkerEventInitialized) return

    const workerList = addWorkerModalTemplate.querySelector('.worker-list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }

    // Use event delegation but be more specific about what triggers the action
    workerList.addEventListener('click', e => {
        // Only proceed if clicked on checkbox, label, or worker-checkbox div
        const workerCheckbox = e.target.closest('.worker-checkbox')
        if (!workerCheckbox) return

        // Prevent multiple triggers
        e.stopPropagation()
        e.preventDefault()

        const checkbox = workerCheckbox.querySelector('input[type="checkbox"]')
        if (!checkbox) return

        // Toggle the checkbox state
        const wasChecked = checkbox.checked
        checkbox.checked = !wasChecked

        // Update selectedUsers map
        const workerId = checkbox.id
        if (checkbox.checked) {
            selectedUsers.push(workerId)
        } else {
            const index = selectedUsers.indexOf(workerId)
            if (index !== -1) {
                selectedUsers.splice(index, 1)
            }
        }
    })

    isSelectWorkerEventInitialized = true
}

// Add Worker -------------------------

export async function addWorker(asyncFunction, action = () => { }) {
    if (!asyncFunction || typeof asyncFunction !== 'function') {
        console.error('Invalid asyncFunction provided to addWorker.')
        return
    }

    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    if (!addWorkerModalTemplate) {
        console.error('Add Worker Modal template not found.')
        Dialog.somethingWentWrong()
        return
    }

    const confirmAddWorkerButton = addWorkerModalTemplate.querySelector('#confirm_add_worker_button')
    if (!confirmAddWorkerButton) {
        console.error('Confirm Add Worker button not found.')
        Dialog.somethingWentWrong()
        return
    }

    confirmAddWorkerButton.addEventListener('click', e => debounceAsync(addWorkerButtonEvent(e, confirmAddWorkerButton, asyncFunction, action), 300))
}

async function addWorkerButtonEvent(e, confirmAddWorkerButton, asyncFunction, action) {
    e.preventDefault()

    if (selectedUsers.length === 0) {
        Dialog.errorOccurred('No workers selected. Please select at least one worker to add.')
        return
    }

    if (!await confirmationDialog(
        'Add Workers',
        `Are you sure you want to add ${selectedUsers.length} worker(s) to this project?`,
    )) return

    const addWorkerButton = document.querySelector('#add_worker_button')
    const projectId = addWorkerButton.dataset.projectid
    if (!projectId) {
        console.error('Project ID not found in modal dataset.')
        Dialog.somethingWentWrong()
        return
    }

    Loader.patch(confirmAddWorkerButton.querySelector('.text-w-icon'))
    try {
        const result = await asyncFunction(projectId, selectedUsers)
        if (typeof action === 'function') action(result)

        // Close the modal
        const cancelButton = addWorkerModalTemplate.querySelector('#cancel_add_worker_button')
        cancelButton?.click()
    } catch (error) {
        console.error(error)
        Dialog.errorOccurred('An error occurred while adding workers. Please try again.')
    } finally {
        Loader.delete()
    }
}
