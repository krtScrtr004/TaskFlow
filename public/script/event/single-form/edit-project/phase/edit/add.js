import { addPhase } from '../add-phase.js'

import { Http } from '../../../../../utility/http.js'
import { Dialog } from '../../../../../render/dialog.js'

let isLoading = false
async function sendToBackend(data) {
    if (isLoading) return
    isLoading = true

    const response = await Http.POST('add-phase', data)
    if (!response) {
        throw new Error('No response from server')
    }

    if (!response.data?.id) {
        throw new Error('Invalid response from server')
    }

    isLoading = false
    return response.data
}

async function action(data) {
    const response = await sendToBackend(data)
    data.id = response.id
    return data
}

try {
    addPhase({action: action})
} catch (error) {
    console.error('Error initializing addPhase:', error)
    Dialog.errorOccurred('Failed to initialize add phase functionality. Please refresh the page and try again.')
}
