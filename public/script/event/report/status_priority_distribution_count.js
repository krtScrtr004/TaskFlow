document.addEventListener('DOMContentLoaded', () => {
    const combinationBreakdownEl = document.querySelector('.combination-breakdown')
    const chartCanvas = document.querySelector('#status_priority_distribution')

    if (!combinationBreakdownEl) {
        console.warn('Combination breakdown element not found.')
        return
    }

    if (!chartCanvas) {
        console.warn('Status x Priority Distribution chart not found.')
        return
    }

    // Extract combination data from DOM
    const statusLabels = ['Pending', 'On Going', 'Completed', 'Delayed', 'Cancelled']
    
    // Initialize data structure for each priority
    const highPriorityData = []
    const mediumPriorityData = []
    const lowPriorityData = []

    // Parse data from DOM
    const statusGroups = combinationBreakdownEl.querySelectorAll('.status-group')
    
    statusGroups.forEach((statusGroup, statusIndex) => {
        const prioritySpans = statusGroup.querySelectorAll('span')
        
        prioritySpans.forEach((span, priorityIndex) => {
            const percentage = parseFloat(span.dataset.percentage) || 0
            
            if (priorityIndex === 0) {
                lowPriorityData.push(percentage)
            } else if (priorityIndex === 1) {
                mediumPriorityData.push(percentage)
            } else if (priorityIndex === 2) {
                highPriorityData.push(percentage)
            }
        })
    })

    // Initialize Chart.js with clustered bars
    const ctx = chartCanvas.getContext('2d')

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: statusLabels,
            datasets: [
                {
                    label: 'High Priority',
                    data: highPriorityData,
                    backgroundColor: 'rgba(244, 67, 54, 0.9)',
                    borderColor: 'rgba(244, 67, 54, 1)',
                    borderWidth: 1.5
                },
                {
                    label: 'Medium Priority',
                    data: mediumPriorityData,
                    backgroundColor: 'rgba(33, 150, 243, 0.9)',
                    borderColor: 'rgba(33, 150, 243, 1)',
                    borderWidth: 1.5
                },
                {
                    label: 'Low Priority',
                    data: lowPriorityData,
                    backgroundColor: 'rgba(76, 175, 80, 0.9)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 1.5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: {
                            size: 12
                        },
                        padding: 8
                    }
                },
                title: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const value = context.parsed.y
                            return `${context.dataset.label}: ${value.toFixed(2)}%`
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
            scales: {
                x: {
                    type: 'category',
                    title: {
                        display: true,
                        text: 'Task Status',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 10
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false,
                        drawBorder: true
                    },
                },
                y: {
                    type: 'linear',
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Percentage (%)',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 10
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
                        callback: function(value) {
                            return value.toFixed(0) + '%'
                        }
                    }
                }
            },
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 10,
                    bottom: 10
                }
            }
        }
    })
})
