document.addEventListener('DOMContentLoaded', () => {
    const periodicCount = document.querySelector('.task-statistics > .periodic-count')

    const canvas = periodicCount?.querySelector('#task_periodic_count_chart')
    if (!canvas) {
        console.error('Task periodic counts chart canvas not found.')
        return
    }

    // Get all year data containers
    const yearContainers = periodicCount?.querySelectorAll('[data-year]')
    
    // Month names mapping
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ]

    // Parse data from DOM
    const monthData = {}
    let allMonthYears = new Set()

    if (yearContainers && yearContainers.length > 0) {
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
    }

    // Handle empty data with placeholder
    let sortedLabels
    let sortedData

    if (allMonthYears.size === 0) {
        console.warn('No periodic task count data found, showing placeholder')
        
        // Create placeholder data for current month
        const now = new Date()
        const currentMonth = monthNames[now.getMonth()].substring(0, 3)
        const currentYear = now.getFullYear()
        
        sortedLabels = [`${currentMonth} ${currentYear}`]
        sortedData = [0]
    } else {
        // Sort labels chronologically
        sortedLabels = Array.from(allMonthYears).sort((a, b) => {
            const [monthA, yearA] = a.split(' ')
            const [monthB, yearB] = b.split(' ')
            
            if (yearA !== yearB) {
                return parseInt(yearA) - parseInt(yearB)
            }
            
            return monthNames.findIndex(m => m.startsWith(monthA)) - 
                    monthNames.findIndex(m => m.startsWith(monthB))
        })

        // Map sorted labels to their counts
        sortedData = sortedLabels.map(label => monthData[label] || 0)
    }

    // Calculate max value for y-axis
    const maxValue = Math.max(...sortedData, 10)

    // Calculate responsive font sizes based on viewport
    const getResponsiveFontSizes = () => {
        const width = window.innerWidth

         if (width <= 575) {
            return {
                legend: 9,
                legendBoxWidth: 10,
                legendPadding: 15,
                tooltip: { title: 10, body: 9 },
                tooltipPadding: 8,
                axisTitle: 9,
                axisTitlePadding: 10,
                xTicks: 7,
                yTicks: 7,
                barThickness: 16,
                maxLabelLength: 8
            }
        } else if (width <= 768) {
            return {
                legend: 9,
                legendBoxWidth: 11,
                legendPadding: 20,
                tooltip: { title: 11, body: 10 },
                tooltipPadding: 10,
                axisTitle: 9,
                axisTitlePadding: 12,
                xTicks: 9,
                yTicks: 9,
                barThickness: 20,
                maxLabelLength: 10
            }
        } else if (width <= 992) {
            return {
                legend: 9,
                legendBoxWidth: 12,
                legendPadding: 25,
                tooltip: { title: 12, body: 11 },
                tooltipPadding: 12,
                axisTitle: 9,
                axisTitlePadding: 13,
                xTicks: 9,
                yTicks: 9,
                barThickness: 22,
                maxLabelLength: 10
            }
        } else {
            return {
                legend: 9,
                legendBoxWidth: 12,
                legendPadding: 30,
                tooltip: { title: 12, body: 11 },
                tooltipPadding: 12,
                axisTitle: 9,
                axisTitlePadding: 15,
                xTicks: 9,
                yTicks: 9,
                barThickness: 24,
                maxLabelLength: 10
            }
        }
    }

    const fontSizes = getResponsiveFontSizes()

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
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Task Creation Timeline',
                    font: {
                        size: fontSizes.title,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                            padding: fontSizes.legendPadding,
                        usePointStyle: true,
                        font: {
                            size: fontSizes.legend
                        }
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
                    padding: fontSizes.tooltipPadding,
                    titleFont: {
                        size: fontSizes.tooltip.title
                    },
                    bodyFont: {
                        size: fontSizes.tooltip.body
                    },
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
                        font: {
                            size: fontSizes.xTicks
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
                        stepSize: Math.ceil(maxValue / 10) || 1,
                        font: {
                            size: fontSizes.yTicks
                        }
                    }
                }
            }
        }
    }

    const ctx = canvas.getContext('2d')
    const chart = new Chart(ctx, config)

    // Handle window resize
    let resizeTimeout
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout)
        resizeTimeout = setTimeout(() => {
            const newFontSizes = getResponsiveFontSizes()
            
            // Update chart options
            chart.options.plugins.title.font.size = newFontSizes.title
            chart.options.plugins.legend.labels.font.size = newFontSizes.legend
            chart.options.plugins.legend.labels.padding = newFontSizes.legendPadding
            chart.options.plugins.tooltip.padding = newFontSizes.tooltipPadding
            chart.options.plugins.tooltip.titleFont.size = newFontSizes.tooltip.title
            chart.options.plugins.tooltip.bodyFont.size = newFontSizes.tooltip.body
            chart.options.scales.x.ticks.font.size = newFontSizes.xTicks
            chart.options.scales.y.ticks.font.size = newFontSizes.yTicks
            
            chart.update()
        }, 250)
    })
})