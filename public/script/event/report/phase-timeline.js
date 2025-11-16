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
    if (typeof(stringDate) !== 'string') {
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
    const projectCompletionDateTime = addOneDay(projectScheduleEl.dataset.projectcompletiondatetime)
    const projectActualCompletionDateTime = projectScheduleEl.dataset.projectactualcompletiondatetime
        ? addOneDay(projectScheduleEl.dataset.projectactualcompletiondatetime)
        : null

    // Extract phase data
    const phases = []
    phaseTimelineDataEl.querySelectorAll('span').forEach(span => {
        const phase = {
            name: span.dataset.name,
            startDateTime: new Date(span.dataset.startdatetime),
            completionDateTime: addOneDay(span.dataset.completiondatetime),
            actualCompletionDateTime: span.dataset.actualcompletiondatetime
                ? addOneDay(span.dataset.actualcompletiondatetime)
                : null
        }
        phases.push(phase)
    })

    if (phases.length === 0) {
        console.warn('No phases found for timeline')
        return
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

    // Initialize Chart.js
    const ctx = chartCanvas.getContext('2d')

    new Chart(ctx, {
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
                        boxWidth: 12,
                        font: {
                            size: 11
                        },
                        padding: 30
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
                    padding: 12,
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 11 },
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
                            size: 16,
                            weight: 'bold'
                        },
                        padding: 15
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    },
                    grid: {
                        display: true,
                        drawBorder: true
                    }
                },
                y: {
                    type: 'linear',
                    offset: true,
                    title: {
                        display: true,
                        text: 'Phases',
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        padding: 15
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        callback: function (value) {
                            const label = labels[value] || ''
                            const maxLength = 10 // Maximum characters per line

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
                    }
                }
            }
        }
    })
})
