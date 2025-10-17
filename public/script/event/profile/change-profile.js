import { Http } from '../../utility/http.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Dialog } from '../../render/dialog.js'
import { Loader } from '../../render/loader.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { debounce } from '../../utility/debounce.js'

let isLoading = false
const profile = document.querySelector('.profile')
const pickProfilePictureButton = profile?.querySelector('#pick_profile_picture_button')
const profilePicker = profile?.querySelector('#profile_picker')
const myId = profile?.dataset.myid

if (!myId || myId.trim() === '') 
    console.warn('User ID not found in form dataset.')

if (pickProfilePictureButton) {
    pickProfilePictureButton.addEventListener('click', debounce(e => {
        e.preventDefault()
        profilePicker.click()
    }, 300))
} else {
    console.error('Pick Profile Picture button not found.')
    Dialog.somethingWentWrong()
}

if (profilePicker) {
    profilePicker.addEventListener('change', async e => {
        const file = e.target.files[0]
        if (!file) {
            console.error('No file selected.')
            Dialog.errorOccurred('No file selected. Please choose an image file.')
            return
        }

        await submit(file)
    })
} else {
    console.warn('Profile picker input not found.')
}

async function submit(file) {
    const formData = new FormData()
    formData.append('profilePicture', file)
    formData.append('_method', 'PATCH')  // Method override for PATCH request

    Loader.patch(pickProfilePictureButton.querySelector('.text-w-icon'))
    try {
        await sendToBackend(formData)

        Dialog.operationSuccess('Profile Picture Updated.', 'Your profile picture has been successfully updated.')
        setTimeout(() => window.location.reload(), 1500)
    } catch (error) {
        console.error('Error during profile picture change:', error)
        errorListDialog(error?.errors, error?.message)
    } finally {
        Loader.delete()
    }
}

async function sendToBackend(formData) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!formData)
            throw new Error('Form data is missing.')

        if (!myId || myId.trim() === '')
            throw new Error('User ID not found.')
        
        // Pass serialize = false to prevent JSON.stringify on FormData
        const response = await Http.POST(`users/${myId}`, formData, false)
        if (!response)
            throw new Error('No response from server.')
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}