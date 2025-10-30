import { addPhase } from '../add-phase.js'
import { Dialog } from '../../../../render/dialog.js'

export const phaseToAdd = new Map() // List of phases to add when edit form is submitted
try {
    addPhase({
        action: function (data) {
            phaseToAdd.set(data.name, data)
        }
    })
} catch (error) {
    console.error('Error initializing addPhase:', error)
    Dialog.somethingWentWrong()
}
