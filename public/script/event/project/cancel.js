import { Dialog } from '../../render/dialog.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Http } from '../../utility/http.js'

let isLoading = false

const cancelProjectButton = document.querySelector('#cancel_project_button')
if (cancelProjectButton) {
    cancelProjectButton.addEventListener('click', async (e) => {
        e.preventDefault()

        if (!await confirmationDialog(
            'Cancel Project',
            'Are you sure you want to cancel this project? This action cannot be undone.',
        )) return

        const mainProjectContent = document.querySelector('.main-project-content')
        if (!mainProjectContent) {
            console.error('Main project content element not found.')
            Dialog.somethingWentWrong()
            return
        }
        const projectId = mainProjectContent.dataset.projectid
        if (!projectId) {
            console.error('Project ID not found in data attributes.')
            Dialog.somethingWentWrong()
            return
        }

        try {
            await sendToBackend(projectId)
            window.location.reload()
        } catch (error) {
            console.error(error)
        }
    })
} else {
    console.error('Cancel Project button not found.')
    Dialog.somethingWentWrong()
}

async function sendToBackend(projectId) {
    if (isLoading) return

    if (!projectId) {
        throw new Error('Project ID is required to cancel the project.')
    }
    isLoading = true

    const response = await Http.POST('cancel-project', { projectId: projectId })
    if (!response) {
        throw new Error('No response from the server.')
    }

    isLoading = false
}