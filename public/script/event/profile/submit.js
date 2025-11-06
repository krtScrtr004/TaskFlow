import { Http } from '../../utility/http.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { validateInputs, userValidationRules } from '../../utility/validator.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { debounce, debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'

let [isLoading, hasSomethingChanged] = [false, false]

const profile = document.querySelector('.profile')

// Store original values
const originalValues = {}

const jobTitleToAdd = []
const jobTitleToRemove = []

const editableProfileDetailsForm = profile?.querySelector('#editable_profile_details_form')
if (!editableProfileDetailsForm) {
    console.error('Editable profile details form not found.')
}

// Capture initial values when the page loads
storeOriginalValues()

// Enable save button on input change
editableProfileDetailsForm?.addEventListener('input', debounce(e => {
    if (e.target.matches('input, textarea') && !hasSomethingChanged) {
        saveChangesButton.disabled = false
        hasSomethingChanged = true
    }
}, 300))
// Handle form submission
editableProfileDetailsForm?.addEventListener('submit', e => debounceAsync(() => submit(e), 300))

const saveChangesButton = editableProfileDetailsForm?.querySelector('#save_changes_button')
if (!saveChangesButton) {
    console.error('Save Changes button not found.')
}

saveChangesButton?.addEventListener('click', e => debounceAsync(() => submit(e), 300))

const myId = profile?.dataset.myid
if (!myId || myId.trim() === '') {
    console.error('User ID not found in form dataset.')
}

/**
 * Handles the submission of the editable profile details form.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Prompts the user for confirmation before saving changes.
 * - Retrieves and validates all required profile input fields.
 * - Validates the input values using custom validation rules.
 * - Determines which fields have changed compared to the original profile data.
 * - If no changes are detected, notifies the user and aborts the operation.
 * - If changes are detected, sends only the changed values to the backend for updating.
 * - Displays a loader during the update process.
 * - On success, notifies the user and reloads the page after a short delay.
 * - Handles and displays any errors that occur during the update process.
 * - Cleans up the loader after the operation completes.
 *
 * @async
 * @function
 * @param {Event} e The form submission event.
 * @returns {Promise<void>} Resolves when the profile update process is complete.
 */
async function submit(e) {
    e.preventDefault()

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Confirm Save Changes',
        'Are you sure you want to save the changes to your profile?'
    )) return

    // Retrieve input fields from the form
    const firstNameInput = editableProfileDetailsForm.querySelector('#first_name')
    const middleNameInput = editableProfileDetailsForm.querySelector('#middle_name')
    const lastNameInput = editableProfileDetailsForm.querySelector('#last_name')
    const emailInput = editableProfileDetailsForm.querySelector('#email')
    const contactNumberInput = editableProfileDetailsForm.querySelector('#contact_number')
    const genderInput = editableProfileDetailsForm.querySelector('input[name="gender"]:checked')
    const bioInput = editableProfileDetailsForm.querySelector('#bio')
    const jobTitlesInput = editableProfileDetailsForm.querySelector('#job_titles')
    if (!firstNameInput || !middleNameInput || !lastNameInput || !emailInput || !contactNumberInput || !genderInput || !bioInput || !jobTitlesInput) {
        console.error('One or more profile form inputs not found.')
        Dialog.somethingWentWrong()
        return
    }

    const currentValues = {
        firstName: firstNameInput.value,
        middleName: middleNameInput.value,
        lastName: lastNameInput.value,
        email: emailInput.value,
        contactNumber: contactNumberInput.value,
        gender: genderInput.value,
        bio: bioInput.value,
        jobTitles: jobTitlesInput.value
    }

    // Get only the changed values
    const changedParams = getChangedValues(currentValues)

    // If nothing changed, don't send request
    if (Object.keys(changedParams).length === 0) {
        Dialog.operationSuccess(
            'No Changes',
            'No changes were detected in your profile.'
        )
        return
    }

    // If job titles changed, populate the add/remove arrays
    if (changedParams.hasOwnProperty('jobTitles')) {
        getJobTitleChanges(
            originalValues.jobTitles,
            currentValues.jobTitles
        )

        // Set jobTitles to an object with toAdd and toRemove arrays
        changedParams.jobTitles = {
            toAdd: jobTitleToAdd,
            toRemove: jobTitleToRemove
        }
    }

    // Validate only the changed inputs
    if (!validateInputs(changedParams, userValidationRules())) { 
        return
    }

    Loader.patch(saveChangesButton.querySelector('.text-w-icon'))
    try {
        await sendToBackend(changedParams)

        setTimeout(() => window.location.reload(), 3000)
        Dialog.operationSuccess(
            'Profile Updated',
            `Your profile has been successfully updated`
        )
    } catch (error) {
        handleException(error, `Error during profile update: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Stores the current values of the editable profile form fields into the `originalValues` object.
 *
 * This function queries the form for the following fields and saves their current values:
 * - first name
 * - middle name
 * - last name
 * - email
 * - contact number
 * - gender (radio input)
 * - bio
 * - job titles
 *
 * If a field is not present, its value is set to an empty string.
 *
 * @function
 * @returns {void}
 */
function storeOriginalValues() {
    const firstNameInput = editableProfileDetailsForm.querySelector('#first_name')
    const middleNameInput = editableProfileDetailsForm.querySelector('#middle_name')
    const lastNameInput = editableProfileDetailsForm.querySelector('#last_name')
    const emailInput = editableProfileDetailsForm.querySelector('#email')
    const contactNumberInput = editableProfileDetailsForm.querySelector('#contact_number')
    const genderInput = editableProfileDetailsForm.querySelector('input[name="gender"]:checked')
    const bioInput = editableProfileDetailsForm.querySelector('#bio')
    const jobTitlesInput = editableProfileDetailsForm.querySelector('#job_titles')

    originalValues.firstName = firstNameInput?.value || ''
    originalValues.middleName = middleNameInput?.value || ''
    originalValues.lastName = lastNameInput?.value || ''
    originalValues.email = emailInput?.value || ''
    originalValues.contactNumber = contactNumberInput?.value || ''
    originalValues.gender = genderInput?.value || ''
    originalValues.bio = bioInput?.value || ''
    originalValues.jobTitles = jobTitlesInput?.value || ''
}

/**
 * Returns an object containing only the properties from currentParams that have changed compared to originalValues.
 *
 * This function compares each key-value pair in currentParams with the corresponding value in originalValues.
 * If a value has changed, it is included in the returned object.
 *
 * @param {Object} currentParams - An object representing the current parameter values, with keys as parameter names and values as their current state.
 *      - [key: string]: any The current value for each parameter.
 * 
 * @returns {Object} An object containing only the changed key-value pairs.
 *      - [key: string]: any The new value for each parameter that has changed.
 */
function getChangedValues(currentParams) {
    const changedValues = {}

    for (const [key, value] of Object.entries(currentParams)) {
        // Compare current value with original value
        if (originalValues[key] !== value) {
            changedValues[key] = value
        }
    }

    return changedValues
}

/**
 * Compares original and current job titles and returns arrays of added and removed titles.
 *
 * This function parses comma-separated job title strings, normalizes them by trimming whitespace,
 * and identifies which titles were added (present in current but not in original) and which were
 * removed (present in original but not in current).
 *
 * @param {string} originalJobTitles - Comma-separated string of original job titles
 * @param {string} currentJobTitles - Comma-separated string of current job titles
 * 
 * @returns {void}
 * 
 */
function getJobTitleChanges(originalJobTitles, currentJobTitles) {
    // Parse and normalize job titles
    const originalTitles = originalJobTitles
        .split(',')
        .map(title => title.trim())
        .filter(title => title !== '')
    
    const currentTitles = currentJobTitles
        .split(',')
        .map(title => title.trim())
        .filter(title => title !== '')
    
    // Find added titles (in current but not in original)
    const addedTitles = currentTitles.filter(title => !originalTitles.includes(title))
    
    // Find removed titles (in original but not in current)
    const removedTitles = originalTitles.filter(title => !currentTitles.includes(title))
    
     // Clear and populate the arrays
    jobTitleToAdd.length = 0
    jobTitleToRemove.length = 0
    jobTitleToAdd.push(...addedTitles)
    jobTitleToRemove.push(...removedTitles)
}

/**
 * Sends updated user profile data to the backend via a PATCH request.
 *
 * This function performs validation only on the fields present in the `params` object.
 * It ensures required fields are not empty if they are being updated, converts the `jobTitles`
 * string to an array, and prevents concurrent requests using an `isLoading` flag.
 *
 * @param {Object} params Object containing the fields to update. Possible keys:
 *      - firstName: {string} (optional) User's first name
 *      - lastName: {string} (optional) User's last name
 *      - email: {string} (optional) User's email address
 *      - contactNumber: {string} (optional) User's contact number
 *      - gender: {string} (optional) User's gender
 *      - jobTitles: {string} (optional) Comma-separated job titles
 *      - [other fields]: {any} (optional) Any additional fields to update
 *
 * @throws {Error} If a required field is missing or empty, if user ID is not found,
 *                 if a request is already in progress, or if the server does not respond.
 *
 * @returns {Promise<void>} Resolves when the request completes successfully.
 */
async function sendToBackend(params) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!myId || myId.trim() === '') {
            throw new Error('User ID not found.')
        }

        // Only validate required fields if they were changed
        if (params.hasOwnProperty('firstName') && (!params.firstName || params.firstName.trim() === '')) {
            throw new Error('First name is required.')
        }
        if (params.hasOwnProperty('lastName') && (!params.lastName || params.lastName.trim() === '')) {
            throw new Error('Last name is required.')
        }
        if (params.hasOwnProperty('email') && (!params.email || params.email.trim() === '')) {
            throw new Error('Email is required.')
        }
        if (params.hasOwnProperty('contactNumber') && (!params.contactNumber || params.contactNumber.trim() === '')) {
            throw new Error('Contact number is required.')
        }
        if (params.hasOwnProperty('gender') && (!params.gender || params.gender.trim() === '')) {
            throw new Error('Gender is required.')
        }
        if (params.hasOwnProperty('jobTitles') && (!params.jobTitles || params.jobTitles.trim() === '')) {
            throw new Error('Job titles are required.')
        }

        // Build request params
        const requestParams = { ...params }
        
        // If job titles changed, send the add/remove arrays instead of full list
        if (requestParams.hasOwnProperty('jobTitles')) {
            delete requestParams.jobTitles
            requestParams.jobTitlesToAdd = jobTitleToAdd
            requestParams.jobTitlesToRemove = jobTitleToRemove
        }

        const response = await Http.PATCH(`users/${myId}`, requestParams)
        if (!response) {
            throw new Error('No response from server.')
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}
