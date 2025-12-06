/**
 * Adds one day to a given date string and returns the resulting Date object.
 *
 * This function parses the input string as a date, increments the day by one,
 * and returns the new Date object. If the input is not a string, the function returns undefined.
 *
 * @param {string} stringDate - The date string to increment (e.g., "2024-06-01").
 * @returns {Date|undefined} The new Date object with one day added, or undefined if input is invalid.
 */
function addOneDay(stringDate) {
    if (typeof (stringDate) !== 'string') {
        return
    }

    const date = new Date(stringDate)
    date.setDate(date.getDate() + 1)
    return date
}

document.addEventListener('DOMContentLoaded', () => {
    const projectScheduleEl = document.querySelector('.project-schedule')
    const phaseTimelineDataEl = document.querySelector('.phase-timeline-data')
    const chartCanvas = document.getElementById('phase_timeline_chart')

    if (!projectScheduleEl) {
        console.warn('Project schedule element not found.')
        return
    }

    if (!phaseTimelineDataEl) {
        console.warn('Phase Timeline date not found.')
        return
    }

    if (!chartCanvas) {
        console.warn('Phase Timeline chart not found.')
        return
    }

    // Extract project schedule
    const projectStartDateTime = new Date(projectScheduleEl.dataset.projectstartdatetime)
    const projectCompletionDateTime = new Date(projectScheduleEl.dataset.projectcompletiondatetime)
    const projectActualCompletionDateTime = projectScheduleEl.dataset.projectactualcompletiondatetime
        ? new Date(projectScheduleEl.dataset.projectactualcompletiondatetime)
        : null

    // Extract phase data
    const phases = []
    phaseTimelineDataEl.querySelectorAll('span').forEach(span => {
        const phase = {
            name: span.dataset.name,
            startDateTime: new Date(span.dataset.startdatetime),
            completionDateTime: new Date(span.dataset.completiondatetime),
            actualCompletionDateTime: span.dataset.actualcompletiondatetime
                ? new Date(span.dataset.actualcompletiondatetime)
                : null
        }
        phases.push(phase)
    })

    // Handle empty phases with placeholder data
    if (phases.length === 0) {
        console.warn('No phases found for timeline, showing placeholder')

        // Create placeholder phase spanning the entire project timeline
        const placeholderPhase = {
            name: 'No phases available',
            startDateTime: projectStartDateTime,
            completionDateTime: projectCompletionDateTime,
            actualCompletionDateTime: null
        }
        phases.push(placeholderPhase)
    }

    // Prepare chart data
    const labels = phases.reverse().map(phase => phase.name)

    // Create datasets for planned timeline
    const plannedDataset = {
        label: 'Planned',
        data: phases.map((phase, idx) => ({
            x: [phase.startDateTime, phase.completionDateTime],
            y: idx,
            phase: phase
        })),
        backgroundColor: 'rgba(76, 175, 80, 0.6)',
        borderColor: 'rgba(76, 175, 80, 1)',
        borderWidth: 2,
        barThickness: 24
    }

    const today = new Date()

    // Create datasets for actual timeline
    const actualDataset = {
        label: 'Actual',
        data: phases.map((phase, idx) => {
            if (phase.startDateTime > today) {
                return {
                    x: [],
                    y: idx,
                    phase: phase,
                    isIncomplete: true
                }
            } else if (!phase.actualCompletionDateTime) {
                // For ongoing phases, show progress up to today
                return {
                    x: [phase.startDateTime, today],
                    y: idx,
                    phase: phase,
                    isIncomplete: true
                }
            }

            const isLate = phase.actualCompletionDateTime > phase.completionDateTime

            return {
                x: [phase.startDateTime, phase.actualCompletionDateTime],
                y: idx,
                phase: phase,
                isLate: isLate
            }
        }),
        backgroundColor: phases.map((phase, idx) => {
            if (!phase.actualCompletionDateTime) {
                return 'rgba(158, 158, 158, 0.4)'
            }
            const isLate = phase.actualCompletionDateTime > phase.completionDateTime
            return isLate ? 'rgba(244, 67, 54, 0.6)' : 'rgba(33, 150, 243, 0.6)'
        }),
        borderColor: phases.map((phase, idx) => {
            if (!phase.actualCompletionDateTime) {
                return 'rgba(158, 158, 158, 0.8)'
            }
            const isLate = phase.actualCompletionDateTime > phase.completionDateTime
            return isLate ? 'rgba(244, 67, 54, 1)' : 'rgba(33, 150, 243, 1)'
        }),
        borderWidth: 2,
        borderDash: phases.map(phase => !phase.actualCompletionDateTime ? [5, 5] : []),
        barThickness: 24
    }

    const datasets = [plannedDataset, actualDataset]

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
                axisTitle: 12,
                axisTitlePadding: 10,
                xTicks: 9,
                yTicks: 9,
                barThickness: 16,
                maxLabelLength: 8
            }
        } else if (width <= 768) {
            return {
                legend: 10,
                legendBoxWidth: 11,
                legendPadding: 20,
                tooltip: { title: 11, body: 10 },
                tooltipPadding: 10,
                axisTitle: 14,
                axisTitlePadding: 12,
                xTicks: 9,
                yTicks: 9,
                barThickness: 20,
                maxLabelLength: 10
            }
        } else if (width <= 992) {
            return {
                legend: 11,
                legendBoxWidth: 12,
                legendPadding: 25,
                tooltip: { title: 12, body: 11 },
                tooltipPadding: 12,
                axisTitle: 15,
                axisTitlePadding: 13,
                xTicks: 9,
                yTicks: 9,
                barThickness: 22,
                maxLabelLength: 10
            }
        } else {
            return {
                legend: 11,
                legendBoxWidth: 12,
                legendPadding: 30,
                tooltip: { title: 12, body: 11 },
                tooltipPadding: 12,
                axisTitle: 16,
                axisTitlePadding: 15,
                xTicks: 9,
                yTicks: 9,
                barThickness: 24,
                maxLabelLength: 10
            }
        }
    }

    const fontSizes = getResponsiveFontSizes()

    // Update bar thickness based on screen size
    plannedDataset.barThickness = fontSizes.barThickness
    actualDataset.barThickness = fontSizes.barThickness

    // Initialize Chart.js
    const ctx = chartCanvas.getContext('2d')

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            indexAxis: 'y',
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
                        title: (context) => {
                            const dataPoint = context[0].raw
                            return dataPoint.phase.name
                        },
                        label: (context) => {
                            const dataPoint = context.raw
                            const phase = dataPoint.phase
                            const isActual = context.datasetIndex === 1

                            const startDate = phase.startDateTime.toLocaleDateString()
                            let endDate

                            if (isActual && !phase.actualCompletionDateTime) {
                                // Ongoing phase - show today as end
                                endDate = new Date().toLocaleDateString()
                            } else if (isActual && phase.actualCompletionDateTime) {
                                endDate = phase.actualCompletionDateTime.toLocaleDateString()
                            } else {
                                endDate = phase.completionDateTime.toLocaleDateString()
                            }

                            const type = isActual ? (phase.actualCompletionDateTime ? 'Actual' : 'Ongoing') : 'Planned'
                            return [
                                `${type} Timeline`,
                                `Start: ${startDate}`,
                                `End: ${endDate}`
                            ]
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: fontSizes.tooltipPadding,
                    titleFont: { size: fontSizes.tooltip.title, weight: 'bold' },
                    bodyFont: { size: fontSizes.tooltip.body },
                    borderColor: 'rgba(255, 255, 255, 0.3)',
                    borderWidth: 1,
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        displayFormats: {
                            day: 'MMM dd, yyyy'
                        }
                    },
                    min: projectStartDateTime,
                    max: projectActualCompletionDateTime || projectCompletionDateTime,
                    title: {
                        display: true,
                        text: 'Timeline',
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
                        display: true,
                        drawBorder: true
                    }
                },
                y: {
                    type: 'category',
                    offset: true,
                    title: {
                        display: true,
                        text: 'Phases',
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
                        autoSkip: false,
                        padding: 1,
                        callback: function (value, index) {
                            const label = this.getLabelForValue(index)
                            if (!label) return ''

                            const maxLength = fontSizes.maxLabelLength

                            if (label.length <= maxLength) {
                                return label
                            }

                            // Split into multiple lines
                            const words = label.split(' ')
                            const lines = []
                            let currentLine = ''

                            words.forEach(word => {
                                if ((currentLine + word).length > maxLength) {
                                    if (currentLine) lines.push(currentLine)
                                    currentLine = word
                                } else {
                                    currentLine += (currentLine ? ' ' : '') + word
                                }
                            })

                            if (currentLine) lines.push(currentLine)
                            return lines
                        }
                    },
                    grid: {
                        display: true,
                        drawBorder: true,
                        lineWidth: 0,
                        tickLength: 20
                    },
                    categoryPercentage: 0.7,
                    barPercentage: 0.9
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

            // Update bar thickness
            chart.data.datasets[0].barThickness = newFontSizes.barThickness
            chart.data.datasets[1].barThickness = newFontSizes.barThickness

            chart.update()
        }, 250)
    })
})
