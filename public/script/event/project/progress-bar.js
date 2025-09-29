const projectSecondaryInfo = document.querySelector('.project-secondary-info')
const projectProgressPercentage = projectSecondaryInfo.querySelector('.progress-percentage')

const projectPercentage = projectProgressPercentage.getAttribute('data-projectPercentage')

const progressBar = projectSecondaryInfo.querySelector('#project_progress_bar')

// Set progress dynamically
progressBar.style.width = projectPercentage + '%'
