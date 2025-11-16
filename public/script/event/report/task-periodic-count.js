document.addEventListener('DOMContentLoaded', () => {
    const periodicCount = document.querySelector('.task-statistics > .periodic-count')

    const canvas = periodicCount?.querySelector('#task_periodic_count_chart')
    if (!canvas) {
        console.error('Task periodic counts chart canvas not found.')
        return
    }

    // Get all year data containers
    const yearContainers = periodicCount?.querySelectorAll('[data-year]')
    if (!yearContainers || yearContainers.length === 0) {
        console.error('Periodic task count data not found.')
        return
    }

    // Month names mapping
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ]

    // Parse data from DOM
    const monthData = {}
    let allMonthYears = new Set()

    yearContainers.forEach(yearContainer => {
        const year = yearContainer.dataset.year.trim()
        const monthSpans = yearContainer.querySelectorAll('[data-month]')
        
        monthSpans.forEach(monthSpan => {
            const month = parseInt(monthSpan.dataset.month)
            const count = parseInt(monthSpan.dataset.count) || 0
            
            // Create label as "Month Year" (e.g., "Jan 2024")
            const monthYear = `${monthNames[month - 1].substring(0, 3)} ${year}`
            allMonthYears.add(monthYear)
            
            // Sum up counts if month/year already exists
            monthData[monthYear] = (monthData[monthYear] || 0) + count
        })
    })

    // Sort labels chronologically
    const sortedLabels = Array.from(allMonthYears).sort((a, b) => {
        const [monthA, yearA] = a.split(' ')
        const [monthB, yearB] = b.split(' ')
        
        if (yearA !== yearB) {
            return parseInt(yearA) - parseInt(yearB)
        }
        
        return monthNames.findIndex(m => m.startsWith(monthA)) - 
                monthNames.findIndex(m => m.startsWith(monthB))
    })

    // Map sorted labels to their counts
    const sortedData = sortedLabels.map(label => monthData[label] || 0)

    // Calculate max value for y-axis
    const maxValue = Math.max(...sortedData, 10)

    const data = {
        labels: sortedLabels,
        datasets: [{
            label: 'Tasks Created',
            data: sortedData,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#007bff',
            pointBorderColor: '#ffffff',
            pointHoverBackgroundColor: '#ffffff',
            pointHoverBorderColor: '#007bff'
        }]
    }

    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Task Creation Timeline',
                    color: '#ffffff',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#ffffff',
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#007bff',
                    borderWidth: 1,
                    callbacks: {
                        title: function(context) {
                            return context[0].label
                        },
                        label: function(context) {
                            const value = context.parsed.y
                            return `Tasks: ${value}`
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#ffffff',
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    max: Math.ceil(maxValue * 1.1),
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#ffffff',
                        stepSize: Math.ceil(maxValue / 10) || 1,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    }

    const ctx = canvas.getContext('2d')
    const taskMonthlyCountsChart = new Chart(ctx, config)
})