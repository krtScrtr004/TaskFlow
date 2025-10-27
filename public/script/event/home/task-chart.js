const taskStatisticsChart = document.querySelector('.task-statistics-chart')

// Status chart
const statusPercentage = taskStatisticsChart.querySelector('.status-percentage')
if (statusPercentage) {
    createChart('#task_status_chart',
        ['Pending', 'On Going', 'Completed', 'Delayed', 'Cancelled'],
        [
            parseFloat(statusPercentage.dataset.pending) || 0,
            parseFloat(statusPercentage.dataset.ongoing) || 0,
            parseFloat(statusPercentage.dataset.completed) || 0,
            parseFloat(statusPercentage.dataset.delayed) || 0,
            parseFloat(statusPercentage.dataset.cancelled) || 0
        ],
        ['#ffb61e', '#38ff5d', '#007bff', '#cb5835', '#dc3545'],
        350,  // width
        350   // height
    )
} else {
    console.warn('Status percentage data not found.')
}

// Priority chart
const priorityPercentage = taskStatisticsChart.querySelector('.priority-percentage')
if (priorityPercentage) {
    createChart('#task_priority_chart',
        ['Low', 'Medium', 'High'],
        [
            parseFloat(priorityPercentage.dataset.low) || 0,
            parseFloat(priorityPercentage.dataset.medium) || 0,
            parseFloat(priorityPercentage.dataset.high) || 0
        ],
        ['#38ff5d', '#ffb61e', '#dc3545'],
        300,  // width
        300   // height
    )
} else {
    console.warn('Priority percentage data not found.')
}

function createChart(elementId, labels, data, colors, width = 400, height = 400) {
    const chartElement = taskStatisticsChart.querySelector(elementId)
    if (!chartElement) {
        console.error(`Chart element ${elementId} not found!`)
        return
    }

    // Set fixed dimensions for the canvas
    chartElement.width = width
    chartElement.height = height
    chartElement.style.width = width + 'px'
    chartElement.style.height = height + 'px'

    // Check if all data is zero
    const totalData = data.reduce((a, b) => a + b, 0)
    const isAllZero = totalData === 0

    // If all data is zero, add a placeholder segment
    const chartData = isAllZero ? [1] : data
    const chartLabels = isAllZero ? ['No Data'] : labels
    const chartColors = isAllZero ? ['#e0e0e0'] : colors

    // Set fixed dimensions for the chart
    new Chart(chartElement, {
        type: 'pie',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: chartColors,
                borderColor: '#ffffff',      // Add border color (white)
                borderWidth: 2               // Add border width (2px)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        boxWidth: 12,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    enabled: !isAllZero,  // Disable tooltip when showing placeholder
                    callbacks: {
                        label: function (context) {
                            const value = context.parsed || 0
                            const total = context.dataset.data.reduce((a, b) => a + b, 0)
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0
                            return `${context.label || ''}: ${value} (${percentage}%)`
                        }
                    }
                }
            },
            animation: { animateRotate: true, duration: 1000 }
        }
    })
}