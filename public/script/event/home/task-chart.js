document.addEventListener('DOMContentLoaded', () => {
    const taskStatisticsChart = document.querySelector('.task-statistics-chart')

    // Status chart
    const statusStatistics = taskStatisticsChart.querySelector('.status-statistics')
    if (statusStatistics) {
        const statusSpans = statusStatistics.querySelectorAll('span')
        const percentages = []
        const counts = []
        const labels = ['Pending', 'On Going', 'Completed', 'Delayed', 'Cancelled']
        
        statusSpans.forEach((span, idx) => {
            const percentage = parseFloat(span.dataset.percentage) || 0
            const count = parseInt(span.dataset.count) || 0
            percentages.push(percentage)
            counts.push(count)
        })

        createChart('#task_status_chart',
            labels.map((label, idx) => `${label} (${percentages[idx].toFixed(1)}%)`),
            percentages,
            counts,
            ['#ffb61e', '#38ff5d', '#007bff', '#cb5835', '#dc3545'],
            600,  // width
            400   // height
        )
    } else {
        console.warn('Status statistics data not found.')
    }

    // Priority chart
    const priorityStatistics = taskStatisticsChart.querySelector('.priority-statistics')
    if (priorityStatistics) {
        const prioritySpans = priorityStatistics.querySelectorAll('span')
        const percentages = []
        const counts = []
        const labels = ['Low', 'Medium', 'High']
        
        prioritySpans.forEach((span, idx) => {
            const percentage = parseFloat(span.dataset.percentage) || 0
            const count = parseInt(span.dataset.count) || 0
            percentages.push(percentage)
            counts.push(count)
        })

        createChart('#task_priority_chart',
            labels.map((label, idx) => `${label} (${percentages[idx].toFixed(1)}%)`),
            percentages,
            counts,
            ['#38ff5d', '#ffb61e', '#dc3545'],
            600,  // width
            400   // height
        )
    } else {
        console.warn('Priority statistics data not found.')
    }

    function createChart(elementId, labels, percentageData, countData, colors, width = 400, height = 400) {
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
        const totalData = percentageData.reduce((a, b) => a + b, 0)
        const isAllZero = totalData === 0

        // If all data is zero, add a placeholder segment
        const chartData = isAllZero ? [1] : percentageData
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
                            usePointStyle: false,
                            boxWidth: 12,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                if (isAllZero) return 'No Data'
                                const index = context.dataIndex
                                const percentage = percentageData[index]
                                const count = countData[index]
                                return [
                                    `Percentage: ${percentage.toFixed(1)}%`,
                                    `Count: ${count} tasks`
                                ]
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        borderColor: 'rgba(255, 255, 255, 0.3)',
                        borderWidth: 1
                    }
                },
                animation: { animateRotate: true, duration: 1000 }
            }
        })
    }
})