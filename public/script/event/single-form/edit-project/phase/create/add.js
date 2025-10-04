import { addPhase } from '../add-phase.js';

try {
    addPhase({allowDisable: false})
} catch (error) {
    console.error('Error initializing addPhase:', error)
    Dialog.errorOccurred('Failed to initialize add phase functionality. Please refresh the page and try again.')
}