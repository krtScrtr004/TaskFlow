document.addEventListener('DOMContentLoaded', () => {
    const phaseCard = document.querySelectorAll('.phase-progress .phase-card')
    phaseCard.forEach(card => {
        const phaseProgressPercentage = card.querySelector('.progress-percentage')
        const progressBar = card.querySelector('.phase-progress-bar .progress-bar')
        if (progressBar) {
            const percentage = parseFloat(phaseProgressPercentage?.dataset.phasepercentage) || 0.0

            // Set progress dynamically
            progressBar.style.width = percentage + '%'
        } else {
            console.warn('Progress Bar element not found.')
        }
    })
})