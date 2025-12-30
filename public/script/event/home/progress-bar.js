const projectStatistics = document.querySelector('.project-statistics')
const rightSide = projectStatistics?.querySelector('.right')
if (!rightSide)
    console.warn('Project statistics right side element not found')

const projectProgressPercentage = rightSide?.querySelector('.progress-percentage')
const progressBar = rightSide?.querySelector('#project_progress_bar')
if (projectProgressPercentage && progressBar) {
    const projectPercentage = projectProgressPercentage?.getAttribute('data-projectPercentage') ?? 0
    // Set progress dynamically
    progressBar.style.width = projectPercentage + '%'

} else {
    console.warn('Project Progress Percentage or Progress Bar element not found.')
}
