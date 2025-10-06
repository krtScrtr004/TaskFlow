import { addPhase } from '../add-phase.js';

try {
    addPhase({
        allowDisable: false,
        action: function () {
            const noPhasesWall = document.querySelector('.no-phases-wall')
            noPhasesWall?.classList.remove('flex-col')
            noPhasesWall?.classList.add('no-display')
        }
    })
} catch (error) {
    console.error('Error initializing addPhase:', error)
    Dialog.errorOccurred('Failed to initialize add phase functionality. Please refresh the page and try again.')
}