const projectProgressPercentage = document.querySelector('.project-secondary-info .progress-percentage')
const progressBar = document.querySelector('.project-secondary-info #project_progress_bar')
if (projectProgressPercentage && progressBar) {
    const projectPercentage = projectProgressPercentage?.getAttribute('data-projectPercentage') ?? 0

    // Set progress dynamically
    progressBar.style.width = projectPercentage + '%'

} else {
    console.warn('Project Progress Percentage or Progress Bar element not found.')
}

