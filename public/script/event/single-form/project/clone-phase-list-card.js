import { Loader } from '../../../render/loader.js'

let ALLOW_DISABLE = true

/**
 * Clones and appends a phase list card element to the DOM based on provided data.
 *
 * This function creates a new phase card element using the given data, appends it to the
 * container of phases, and scrolls the container to show the newly added card. It also
 * manages a loading indicator during the process and allows for optional disabling of
 * certain features.
 *
 * @param {Object} data Object containing phase information used to create the phase card element.
 *      - id: string|number Unique identifier for the phase
 *      - name: string Name of the phase
 *      - description: string (optional) Description of the phase
 *      - status: string (optional) Status of the phase
 *      - [other properties as required by createPhaseElement]
 * @param {boolean} [allowDisable=true] Whether disabling features is allowed for the cloned card.
 *
 * @returns {void}
 */
export function clonePhaseListCard(data, allowDisable = true) {
    ALLOW_DISABLE = allowDisable

    const phases = document.querySelector('.phase-details > .phases')
    
    Loader.trail(phases)

    // Create phase DOM structure from scratch
    const phaseElement = createPhaseElement(data)
    
    phases.appendChild(phaseElement)
    phases.scrollTo({ top: phases.scrollHeight, behavior: 'smooth' })

    Loader.delete()
}

/**
 * Creates a phase element DOM structure for displaying a project phase card.
 *
 * This function constructs a section element representing a project phase, including:
 * - Phase icon and name
 * - Status badge indicating if the phase has started or is completed/cancelled
 * - Cancel button if the phase is not completed or cancelled
 * - Phase details form with relevant information and controls
 *
 * @param {Object} data Object containing phase data with the following properties:
 *      - id: string|number Phase identifier
 *      - name: string Phase name
 *      - startDateTime: string|Date Phase start date/time (ISO string or Date)
 *      - status: string Phase status ('COMPLETED', 'CANCELLED', or other)
 *      - [other properties as required by createPhaseDetailsForm]
 *
 * @returns {HTMLElement} Section element representing the phase card, fully constructed and ready for insertion into the DOM
 */
function createPhaseElement(data) {
    // Calculate phase status
    const startDate = new Date(data.startDateTime);
    const hasStarted = startDate < new Date();
    const isCompleted = data.status === 'COMPLETED' || data.status === 'CANCELLED';
    
    // Create main phase section
    const phaseSection = document.createElement('section');
    phaseSection.className = 'phase';
    phaseSection.dataset.phaseid = data.id || '';
    
    // Create phase header
    const headerDiv = document.createElement('div');
    headerDiv.className = 'flex-row flex-child-center-h flex-space-between';
    
    // Create left side (name and status)
    const leftDiv = document.createElement('div');
    leftDiv.className = 'flex-col';
    
    // Create phase name section
    const nameDiv = document.createElement('div');
    nameDiv.className = 'text-w-icon';
    
    const phaseIcon = document.createElement('img');
    phaseIcon.src = 'asset/image/icon/phase_w.svg';
    phaseIcon.alt = data.name;
    phaseIcon.title = data.name;
    phaseIcon.height = 22;
    
    const phaseNameHeader = document.createElement('h3');
    phaseNameHeader.className = 'phase-name wrap-text single-line-ellipsis';
    phaseNameHeader.title = data.name;
    phaseNameHeader.textContent = data.name;
    
    nameDiv.appendChild(phaseIcon);
    nameDiv.appendChild(phaseNameHeader);
    
    // Create status badge
    const statusBadge = createStatusBadge(hasStarted, isCompleted);
    
    leftDiv.appendChild(nameDiv);
    leftDiv.appendChild(statusBadge);
    
    headerDiv.appendChild(leftDiv);
    
    // Create cancel button if phase can be cancelled
    if (!isCompleted) {
        const cancelButton = createCancelButton(data.name);
        headerDiv.appendChild(cancelButton);
    }
    
    // Create phase details form
    const detailsForm = createPhaseDetailsForm(data, hasStarted, isCompleted);
    
    phaseSection.appendChild(headerDiv);
    phaseSection.appendChild(detailsForm);
    
    return phaseSection;
}

/**
 * Creates a status badge DOM element based on the provided status flags.
 *
 * This function generates a styled badge element to visually represent the status of a task or phase:
 * - If isCompleted is true, the badge is styled as "Completed" with a red background.
 * - If hasStarted is true (and isCompleted is false), the badge is styled as "On Going" with a green background.
 * - If neither flag is true, the badge is styled as "Pending" with a yellow background.
 *
 * @param {boolean} hasStarted Indicates whether the task or phase has started.
 * @param {boolean} isCompleted Indicates whether the task or phase has been completed.
 * 
 * @return {HTMLDivElement} A div element representing the status badge, styled and labeled according to the status.
 */
function createStatusBadge(hasStarted, isCompleted) {
    const statusBadge = document.createElement('div');
    
    if (isCompleted) {
        statusBadge.className = 'status-badge badge center-child red-bg';
        statusBadge.innerHTML = '<p class="center-text white-text">Completed</p>';
    } else if (hasStarted) {
        statusBadge.className = 'status-badge badge center-child green-bg';
        statusBadge.innerHTML = '<p class="center-text white-text">On Going</p>';
    } else {
        statusBadge.className = 'status-badge badge center-child yellow-bg';
        statusBadge.innerHTML = '<p class="center-text black-text">Pending</p>';
    }
    
    return statusBadge;
}

/**
 * Creates a phase details form element for displaying and editing phase information.
 *
 * This function generates a form containing the phase's description, start date, and completion date.
 * The form fields are conditionally enabled or disabled based on the phase's status:
 * - The description field is disabled if the phase is completed.
 * - The start date field is disabled if the phase has started.
 * - The completion date field is disabled if the phase is completed.
 * The function uses helper functions `createInputContainer` and `formatDateForInput` to build the form fields.
 *
 * @param {Object} data Object containing phase data with the following properties:
 *      - name: {string} Name of the phase (used for input names/IDs)
 *      - description: {string} (optional) Description of the phase
 *      - startDateTime: {string|Date} (optional) Start date/time of the phase
 *      - completionDateTime: {string|Date} (optional) Completion date/time of the phase
 * @param {boolean} hasStarted Indicates if the phase has already started (disables start date input if true)
 * @param {boolean} isCompleted Indicates if the phase is completed (disables description and completion date inputs if true)
 *
 * @returns {HTMLDivElement} The constructed phase details form element
 */
function createPhaseDetailsForm(data, hasStarted, isCompleted) {
    const detailsForm = document.createElement('div');
    detailsForm.className = 'phase-details-form flex-col';
    
    // Create description section
    const descriptionContainer = createInputContainer(
        'description',
        'Description',
        'textarea',
        data.description || 'No description provided.',
        data.name + '_description',
        isCompleted ? 'disabled' : ''
    );
    
    // Create secondary info container
    const secondaryInfo = document.createElement('div');
    secondaryInfo.className = 'phase-secondary-info flex-row';
    
    // Create start date input
    const startDateContainer = createInputContainer(
        'start',
        'Start Date',
        'date',
        formatDateForInput(data.startDateTime),
        data.name + '_start_date',
        hasStarted ? 'disabled' : ''
    );
    
    // Create completion date input
    const completionDateContainer = createInputContainer(
        'complete',
        'Completion Date',
        'date',
        formatDateForInput(data.completionDateTime),
        data.name + '_completion_date',
        isCompleted ? 'disabled' : ''
    );
    
    secondaryInfo.appendChild(startDateContainer);
    secondaryInfo.appendChild(completionDateContainer);
    
    detailsForm.appendChild(descriptionContainer);
    detailsForm.appendChild(secondaryInfo);
    
    return detailsForm;
}

/**
 * Creates a labeled input container element with an icon and input field.
 *
 * This function generates a container <div> that includes:
 * - A label with an icon and descriptive text
 * - An input field or textarea, depending on the specified type
 * - Optional disabling of the input if allowed by a global flag
 *
 * @param {string} iconType The icon name (without extension) to display in the label (e.g., 'calendar', 'user')
 * @param {string} labelText The text to display as the label for the input
 * @param {string} inputType The type of input to create ('text', 'date', 'textarea', etc.)
 * @param {string} value The initial value to set for the input or textarea
 * @param {string} inputId The id and name attribute for the input element
 * @param {boolean} disabled Whether the input should be disabled (if ALLOW_DISABLE is true)
 *
 * @returns {HTMLDivElement} The constructed container element with label and input
 */
function createInputContainer(iconType, labelText, inputType, value, inputId, disabled) {
    const container = document.createElement('div');
    container.className = 'input-label-container';
    
    // Create label
    const label = document.createElement('label');
    label.htmlFor = inputId;
    
    const labelDiv = document.createElement('div');
    labelDiv.className = 'text-w-icon';
    
    const icon = document.createElement('img');
    icon.src = `asset/image/icon/${iconType}_w.svg`;
    icon.alt = labelText;
    icon.title = labelText;
    icon.height = 20;
    
    const labelP = document.createElement('p');
    labelP.textContent = labelText;
    
    labelDiv.appendChild(icon);
    labelDiv.appendChild(labelP);
    label.appendChild(labelDiv);
    
    // Create input
    let input;
    if (inputType === 'textarea') {
        input = document.createElement('textarea');
        input.className = 'phase-description';
        input.rows = 5;
        input.cols = 10;
        input.textContent = value;
    } else {
        input = document.createElement('input');
        input.type = inputType;
        input.value = value;
        input.required = true;
        
        if (inputType === 'date') {
            input.className = inputId.includes('start') ? 'phase-start-datetime' : 'phase-completion-datetime';
        }
    }
    
    input.name = inputId;
    input.id = inputId;
    
    if (disabled && ALLOW_DISABLE) {
        input.disabled = true;
    }
    
    container.appendChild(label);
    container.appendChild(input);
    
    return container;
}

/**
 * Formats a date string into the 'YYYY-MM-DD' format for use in HTML date input fields.
 *
 * This function parses the provided date string, creates a JavaScript Date object,
 * and returns a string formatted as 'YYYY-MM-DD', which is compatible with HTML date inputs.
 *
 * @param {string} dateString The date string to format (can be any valid date string accepted by the Date constructor).
 * @returns {string} The formatted date string in 'YYYY-MM-DD' format.
 */
function formatDateForInput(dateString) {
    const date = new Date(dateString);
    return date.toISOString().split('T')[0]; // Format as YYYY-MM-DD
}

/**
 * Creates a cancel button element for a given phase name.
 *
 * This function generates a button element styled for canceling a phase, 
 * including an icon and appropriate accessibility attributes.
 * - The button has type "button" and classes "cancel-phase-button unset-button".
 * - The button contains an <img> element as an icon.
 * - The icon uses the "delete_r.svg" asset, with alt and title attributes set to the phase name.
 * - The icon's height is set to 20 pixels.
 *
 * @param {string} phaseName The name of the phase to be canceled, used for accessibility labels.
 * @returns {HTMLButtonElement} A button element configured for canceling the specified phase.
 */
function createCancelButton(phaseName) {
    const button = document.createElement('button')
    button.type = 'button'
    button.className = 'cancel-phase-button unset-button'

    const icon = document.createElement('img')
    icon.src = 'asset/image/icon/delete_r.svg'
    icon.alt = `Cancel ${phaseName}`
    icon.title = `Cancel ${phaseName}`
    icon.height = 20

    button.appendChild(icon)
    return button
}