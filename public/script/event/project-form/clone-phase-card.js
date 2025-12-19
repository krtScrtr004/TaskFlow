document.addEventListener('DOMContentLoaded', () => {
    const phaseSection = document.querySelector('#phase_section')
    const noPhasesWall = phaseSection.querySelector('.no-phases-wall')

    const template = phaseSection.querySelector('template#phase_form_card_template')
    if (!template) {
        console.warn('Clone Phase Card: Template not found.')
        return
    }

    const addPhaseButton = phaseSection.querySelector('#add_phase_button')
    if (!addPhaseButton) {
        console.warn('Clone Phase Card: Add Phase button not found.')
        return
    }

    addPhaseButton.addEventListener('click', () => {
        // Clone and insert new phase card
        const clone = template.content.cloneNode(true)
        phaseSection.insertBefore(clone, addPhaseButton)

        const newCard = phaseSection.querySelector('.phase-form-card:last-of-type')
        addRemoveListeners(newCard)

        // Hide no phases wall
        noPhasesWall?.classList.add('no-display')
        noPhasesWall?.classList.remove('flex-col')
    })

    /**
     * Attaches a click listener to the remove button inside a phase card.
     *
     * Locates the '.remove-phase-button' within the provided card and, if found,
     * registers a click handler that:
     *  - removes the card from the DOM,
     *  - queries the outer-scope `phaseSection` for remaining '.phase-form-card' elements,
     *  - if none remain, makes the `noPhasesWall` visible by removing 'no-display' and
     *    adding 'flex-col'.
     *
     * If the remove button is not found, a warning is logged and no listener is attached.
     * Note: this function relies on the presence of `phaseSection` and `noPhasesWall` in
     * the surrounding scope and does not return a value.
     *
     * @param {Element|HTMLElement} card - The DOM element representing a phase form card.
     * @returns {void}
     */
    function addRemoveListeners(card) {
        const removeButton = card.querySelector('.remove-phase-button')
        if (!removeButton) {
            console.warn('Clone Phase Card: Remove button not found in card.')
            return
        }

        removeButton.addEventListener('click', () => {
            card.remove()

            // Check if any phase cards remain, show no phases wall if none
            const remainingCards = phaseSection.querySelectorAll('.phase-form-card')
            if (remainingCards.length === 0) {
                noPhasesWall?.classList.remove('no-display')
                noPhasesWall?.classList.add('flex-col')
            }
        })
    }
})