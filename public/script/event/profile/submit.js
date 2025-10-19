import { Http } from '../../utility/http.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { validateInputs, userValidationRules } from '../../utility/validator.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { debounce, debounceAsync } from '../../utility/debounce.js'

let [isLoading, hasSomethingChanged] = [false, false]
const profile = document.querySelector('.profile')
const editableProfileDetailsForm = profile?.querySelector('#editable_profile_details_form')
const saveChangesButton = editableProfileDetailsForm?.querySelector('#save_changes_button')
const myId = profile?.dataset.myid

// Store original values
const originalValues = {}

if (editableProfileDetailsForm) {
    // Capture initial values when the page loads
    storeOriginalValues()

    editableProfileDetailsForm.addEventListener('input', debounce(e => {
        if (e.target.matches('input, textarea') && !hasSomethingChanged) {
            saveChangesButton.disabled = false
            hasSomethingChanged = true
        }
    }, 300))

    editableProfileDetailsForm.addEventListener('submit', e => debounceAsync(() => submit(e), 300))
} else {
    console.error('Editable profile details form not found.')
}

if (saveChangesButton) {
    saveChangesButton.addEventListener('click', e => debounceAsync(() => submit(e), 300))
} else {
    console.error('Save Changes button not found.')
}

if (!myId || myId.trim() === '')
    console.error('User ID not found in form dataset.')

async function submit(e) {
    e.preventDefault()

    if (!await confirmationDialog(
        'Confirm Save Changes',
        'Are you sure you want to save the changes to your profile?'
    )) return

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

    const params = {
        firstName: firstNameInput.value,
        middleName: middleNameInput.value,
        lastName: lastNameInput.value,
        email: emailInput.value,
        contactNumber: contactNumberInput.value,
        gender: genderInput.value,
        bio: bioInput.value,
        jobTitles: jobTitlesInput.value
    }
    if (!validateInputs(params, userValidationRules())) return

    // Get only the changed values
    const changedParams = getChangedValues(params)

    // If nothing changed, don't send request
    if (Object.keys(changedParams).length === 0) {
        Dialog.operationSuccess(
            'No Changes',
            'No changes were detected in your profile.'
        )
        return
    }

    Loader.patch(saveChangesButton.querySelector('.text-w-icon'))
    try {
        await sendToBackend(changedParams)

        const delay = 3000
        Dialog.operationSuccess(
            'Profile Updated',
            `Your profile has been successfully updated`
        )
        setTimeout(() => window.location.reload(), delay)
    } catch (error) {
        console.error('Error submitting profile changes:', error)
        if (error?.errors) {
            errorListDialog(error?.message, error.errors)
        } else {
            Dialog.somethingWentWrong()
        }
    } finally {
        Loader.delete()
    }
}

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

async function sendToBackend(params) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!myId || myId.trim() === '')
            throw new Error('User ID not found.')

        // Only validate required fields if they were changed
        if (params.hasOwnProperty('firstName') && (!params.firstName || params.firstName.trim() === ''))
            throw new Error('First name is required.')
        if (params.hasOwnProperty('lastName') && (!params.lastName || params.lastName.trim() === ''))
            throw new Error('Last name is required.')
        if (params.hasOwnProperty('email') && (!params.email || params.email.trim() === ''))
            throw new Error('Email is required.')
        if (params.hasOwnProperty('contactNumber') && (!params.contactNumber || params.contactNumber.trim() === ''))
            throw new Error('Contact number is required.')
        if (params.hasOwnProperty('gender') && (!params.gender || params.gender.trim() === ''))
            throw new Error('Gender is required.')
        if (params.hasOwnProperty('jobTitles') && (!params.jobTitles || params.jobTitles.trim() === ''))
            throw new Error('Job titles are required.')

        // Convert jobTitles to array if it exists in changed params
        const requestParams = { ...params }
        if (requestParams.hasOwnProperty('jobTitles')) {
            requestParams.jobTitles = requestParams.jobTitles.split(',').map(title => title.trim()).filter(title => title !== '')
        }

        const response = await Http.PATCH(`users/${myId}`, requestParams)
        if (!response)
            throw new Error('No response from server.')
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}
