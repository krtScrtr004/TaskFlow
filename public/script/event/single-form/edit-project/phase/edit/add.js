import { addPhase } from '../add-phase.js'
import { Dialog } from '../../../../../render/dialog.js'

// List of phases to add when edit form is submitted
export const phaseToAdd = new Map()
try {
    addPhase({
        action: function (data) {
            phaseToAdd.set(data.name, data)
        }
    })
} catch (error) {
    console.error('Error initializing addPhase:', error)
    Dialog.errorOccurred('Failed to initialize add phase functionality. Please refresh the page and try again.')
}
