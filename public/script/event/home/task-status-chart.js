import { die } from '../../utility/utility.js'

const projectContainer = document.querySelector('.project-container')
const taskStatistics = projectContainer?.querySelector('.task-statistics')
if (!taskStatistics) die('Task statistics element not found')

const taskStatisticsChart = taskStatistics.querySelector('#task_statistics_chart')
if (!taskStatisticsChart) die('Task statistics chart element not found')

/**
 * Retrieves task statistics data from the DOM and returns it as an object.
 *
 * This function queries the DOM for an element with the class `task-data` inside the
 * `taskStatistics` container. It extracts task counts (pending, ongoing, completed,
 * delayed, and cancelled) from the element's `data-*` attributes and returns them
 * as an object with integer values.
 *
 * Behavior and side effects:
 * - Throws an error if the `.task-data` element is not found within `taskStatistics`.
 * - Reads the `data-pendingcount`, `data-ongoingcount`, `data-completedcount`,
 *   `data-delayedcount`, and `data-cancelledcount` attributes from the `.task-data` element.
 * - Defaults to `0` for any missing or undefined data attributes.
 * - Converts the extracted values to integers before returning them.
 *
 * @throws Error If the `.task-data` element is not found in the DOM.
 *
 * @return {Object} An object containing task statistics:
 * - `pending` {number} The count of pending tasks.
 * - `ongoing` {number} The count of ongoing tasks.
 * - `completed` {number} The count of completed tasks.
 * - `delayed` {number} The count of delayed tasks.
 * - `cancelled` {number} The count of cancelled tasks.
 */
function getData() {
    const taskData = taskStatistics.querySelector('.task-data')
    if (!taskData) die('Task data element not found')

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

/**
 * Creates a dataset for a chart based on task statuses.
 *
 * This function takes an object containing counts of tasks in various statuses
 * (pending, ongoing, completed, delayed, and cancelled) and returns an array
 * formatted for use as a dataset in a chart. Each status is associated with a
 * specific color for visualization.
 *
 * Behavior and side effects:
 * - Extracts the counts of tasks for each status from the input object.
 * - Constructs a dataset object with labels, data, and background colors.
 * - Returns the dataset as an array containing a single object.
 *
 * @param {Object} data An object containing task status counts.
 * @param {number} data.pending The count of pending tasks.
 * @param {number} data.ongoing The count of ongoing tasks.
 * @param {number} data.completed The count of completed tasks.
 * @param {number} data.delayed The count of delayed tasks.
 * @param {number} data.cancelled The count of cancelled tasks.
 *
 * @returns {Array<Object>} An array containing a single dataset object for the chart.
 */
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
        maintainAspectRatio: false,
        scales: {
            x: {
                stacked: true,
                ticks: {
                    font: {
                        size: 12
                    }
                }
            },
            y: {
                stacked: false,
                ticks: {
                    font: {
                        size: 12
                    },
                }
            }
        },

    }
}

new Chart(taskStatisticsChart, config)