import { Loader } from '../../../../render/loader.js'

let ALLOW_DISABLE = true

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
    phaseNameHeader.className = 'phase-name wrap-text';
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

function formatDateForInput(dateString) {
    const date = new Date(dateString);
    return date.toISOString().split('T')[0]; // Format as YYYY-MM-DD
}

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