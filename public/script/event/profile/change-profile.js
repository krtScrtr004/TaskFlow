import { Http } from '../../utility/http.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const profile = document.querySelector('.profile')

const myId = profile?.dataset.myid
if (!myId || myId.trim() === '') {
    console.warn('User ID not found in form dataset.')
}

const pickProfilePictureButton = profile?.querySelector('#pick_profile_picture_button')
if (!pickProfilePictureButton) {
    console.error('Pick Profile Picture button not found.')
    Dialog.somethingWentWrong()
}

const profilePicker = profile?.querySelector('#profile_picker')
if (!profilePicker) {
    console.warn('Profile picker input not found.')
}

// Handle pick profile picture button click
pickProfilePictureButton?.addEventListener('click', e => {
    e.preventDefault()
    profilePicker.click()
})

// Handle profile picture selection
profilePicker?.addEventListener('change', async e => {
    const file = e.target.files[0]
    if (!file) {
        console.error('No file selected.')
        Dialog.errorOccurred('No file selected. Please choose an image file.')
        return
    }

    await submit(file)
})

/**
 * Submits a new profile picture to the backend after user confirmation.
 *
 * This function performs the following steps:
 * - Prompts the user with a confirmation dialog before proceeding.
 * - Prepares a FormData object containing the selected file and a method override for PATCH requests.
 * - Shows a loading indicator on the profile picture button.
 * - Sends the FormData to the backend to update the profile picture.
 * - Displays a success dialog and reloads the page upon successful update.
 * - Handles any errors that occur during the process and displays an error dialog.
 * - Removes the loading indicator after the operation completes.
 *
 * @async
 * @param {File} file The new profile picture file selected by the user.
 * @returns {Promise<void>} Resolves when the operation is complete.
 */
async function submit(file) {
    try {
        Loader.patch(pickProfilePictureButton.querySelector('.text-w-icon'))

        // Show confirmation dialog
        if (!await confirmationDialog(
            'Confirm Profile Picture Change',
            'Are you sure you want to change your profile picture?'
        )) return

        // Prepare form data
        const formData = new FormData()
        formData.append('profilePicture', file)
        formData.append('_method', 'PATCH')  // Method override for PATCH request

        await sendToBackend(formData)

        // Reload the page after a short delay
        setTimeout(() => window.location.reload(), 1500)
        Dialog.operationSuccess('Profile Picture Updated.', 'Your profile picture has been successfully updated.')
    } catch (error) {
        handleException(error, `Error during profile picture change: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends form data to the backend to update the user's profile.
 *
 * This function performs the following:
 * - Checks if a request is already in progress to prevent duplicate submissions.
 * - Validates the presence of form data and user ID before proceeding.
 * - Sends a POST request to the backend with the provided FormData, without serializing it to JSON.
 * - Handles errors and ensures the loading state is properly managed.
 *
 * @param {FormData} formData The form data containing profile information to be sent to the backend.
 * 
 * @throws {Error} If form data is missing, user ID is not found, or there is no response from the server.
 * 
 * @returns {Promise<void>} Resolves when the request completes successfully, or throws an error if it fails.
 */
async function sendToBackend(formData) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!formData) {
            throw new Error('Form data is missing.')
        }

        if (!myId || myId.trim() === '') {
            throw new Error('User ID not found.')
        }
        
        // Use POST with method override for PATCH
        // Pass serialize = false to prevent JSON.stringify on FormData
        const response = await Http.POST(`users/${myId}`, formData, false)
        if (!response) {
            throw new Error('No response from server.')
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}