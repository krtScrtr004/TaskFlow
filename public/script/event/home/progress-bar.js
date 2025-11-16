document.addEventListener('DOMContentLoaded', () => {
    const projectProgressPercentage = document.querySelector('.progress-percentage')
    const progressBar = document.querySelector('#project_progress_bar')
    if (projectProgressPercentage && progressBar) {
        const projectPercentage = projectProgressPercentage?.getAttribute('data-projectPercentage') ?? 0
        // Set progress dynamically
        progressBar.style.width = projectPercentage + '%'

    } else {
        console.warn('Project Progress Percentage or Progress Bar element not found.')
    }
})