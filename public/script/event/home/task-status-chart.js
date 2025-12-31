import { die } from '../../utility/utility.js'

const projectContainer = document.querySelector('.project-container')
const taskStatistics = projectContainer?.querySelector('.task-statistics')
if (!taskStatistics)
    die('Task statistics element not found')

const taskStatisticsChart = taskStatistics.querySelector('#task_statistics_chart')
if (!taskStatisticsChart)
    die('Task statistics chart element not found')

function getData() {
    const taskData = taskStatistics.querySelector('.task-data')
    if (!taskData)
        die('Task data element not found')

    const pending = taskData.dataset.pendingcount ?? 0
    const ongoing = taskData.dataset.ongoingcount ?? 0
    const completed = taskData.dataset.completedcount ?? 0
    const delayed = taskData.dataset.delayedcount ?? 0
    const cancelled = taskData.dataset.cancelledcount ?? 0

    return {
        pending: parseInt(pending),
        ongoing: parseInt(ongoing),
        completed: parseInt(completed),
        delayed: parseInt(delayed),
        cancelled: parseInt(cancelled)
    }
}

function createDataset(data) {
    const {
        pending,
        ongoing,
        completed,
        delayed,
        cancelled
    } = data

    return [{
        label: '',
        data: [
            pending, 
            ongoing, 
            completed, 
            delayed, 
            cancelled
        ],
        backgroundColor: [
            '#ffb61e',
            '#38ff5d',
            '#007bff',
            '#cb5835',
            '#c42838'
        ],
    }]
}

const config = {
    type: 'bar',
    data: {
        labels: [
            'Pending', 
            'Ongoing', 
            'Completed', 
            'Delayed', 
            'Cancelled'
        ],
        datasets: createDataset(getData()),
    },
    options: {
        plugins: {
            title: {
                display: false,
            },
            legend: {
                display: false,
            }
        },
        responsive: true,
        scales: {
            x: {
                stacked: true,
            },
            y: {
                stacked: false
            }
        }
    }
}

new Chart(taskStatisticsChart, config)