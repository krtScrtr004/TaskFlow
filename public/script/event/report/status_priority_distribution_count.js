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

    // Initialize Chart.js with clustered bars
    const ctx = chartCanvas.getContext('2d')

    const chart = new Chart(ctx, {
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
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: fontSizes.legendBoxWidth,
                        font: {
                            size: fontSizes.legend
                        },
                        padding: fontSizes.legendPadding
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
                    padding: fontSizes.tooltipPadding,
                    titleFont: { size: fontSizes.tooltip.title, weight: 'bold' },
                    bodyFont: { size: fontSizes.tooltip.body },
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
                            size: fontSizes.axisTitle,
                            weight: 'bold'
                        },
                        padding: fontSizes.axisTitlePadding
                    },
                    ticks: {
                        font: {
                            size: fontSizes.xTicks
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
                            size: fontSizes.axisTitle,
                            weight: 'bold'
                        },
                        padding: fontSizes.axisTitlePadding
                    },
                    ticks: {
                        font: {
                            size: fontSizes.yTicks
                        },
                        callback: function(value) {
                            return value.toFixed(0) + '%'
                        }
                    }
                }
            },
            layout: {
                padding: {
                    left: fontSizes.layoutPadding,
                    right: fontSizes.layoutPadding,
                    top: fontSizes.layoutPadding,
                    bottom: fontSizes.layoutPadding
                }
            }
        }
    })

    // Handle window resize
    let resizeTimeout
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout)
        resizeTimeout = setTimeout(() => {
            const newFontSizes = getResponsiveFontSizes()
            
            // Update chart options
            chart.options.plugins.legend.labels.boxWidth = newFontSizes.legendBoxWidth
            chart.options.plugins.legend.labels.font.size = newFontSizes.legend
            chart.options.plugins.legend.labels.padding = newFontSizes.legendPadding
            chart.options.plugins.tooltip.padding = newFontSizes.tooltipPadding
            chart.options.plugins.tooltip.titleFont.size = newFontSizes.tooltip.title
            chart.options.plugins.tooltip.bodyFont.size = newFontSizes.tooltip.body
            chart.options.scales.x.title.font.size = newFontSizes.axisTitle
            chart.options.scales.x.title.padding = newFontSizes.axisTitlePadding
            chart.options.scales.x.ticks.font.size = newFontSizes.xTicks
            chart.options.scales.y.title.font.size = newFontSizes.axisTitle
            chart.options.scales.y.title.padding = newFontSizes.axisTitlePadding
            chart.options.scales.y.ticks.font.size = newFontSizes.yTicks
            chart.options.layout.padding.left = newFontSizes.layoutPadding
            chart.options.layout.padding.right = newFontSizes.layoutPadding
            chart.options.layout.padding.top = newFontSizes.layoutPadding
            chart.options.layout.padding.bottom = newFontSizes.layoutPadding
            
            chart.update()
        }, 250)
    })
})
