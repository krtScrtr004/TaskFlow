import { addPhase } from '../add-phase.js'
import { Dialog } from '../../../../render/dialog.js'
import { handleException } from '../../../../utility/handle-exception.js'

try {
    // Initialize the add phase functionality
    addPhase({
        allowDisable: false, // Disabling phases input is not allowed when creating a project
        action: function () {
            const noPhasesWall = document.querySelector('.no-phases-wall')
            noPhasesWall?.classList.remove('flex-col')
            noPhasesWall?.classList.add('no-display')
        }
    })
} catch (error) {
    handleException(error, 'Error initializing addPhase:', error)
}