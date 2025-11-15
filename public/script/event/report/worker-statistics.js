document.addEventListener('DOMContentLoaded', () => {

    const workerStatistics = document.querySelector('.report .worker-statistics')

    const canvas = workerStatistics?.querySelector('#worker_statistics_chart')
    if (!canvas) {
        console.error('Worker statistics chart canvas not found.')
    }

    const workersCount = workerStatistics?.querySelector('.workers-count')
    if (!workersCount) {
        console.error('Workers count element not found in worker statistics section.')
    }

    const assignedCount = parseInt(workersCount?.dataset.assignedcount.trim()) || 0
    const unassignedCount = parseInt(workersCount?.dataset.unassignedcount.trim()) || 0
    const taskTerminatedCount = parseInt(workersCount?.dataset.taskterminatedcount.trim()) || 0
    const projectTerminatedCount = parseInt(workersCount?.dataset.projectterminatedcount.trim()) || 0

    const workersPercentage = workerStatistics?.querySelector('.workers-percentage')
    if (!workersPercentage) {
        console.error('Workers count element not found in worker statistics section.')
    }

    const assignedPercentage = parseFloat(workersPercentage?.dataset.assignedpercentage.trim()) || 0
    const unassignedPercentage = parseFloat(workersPercentage?.dataset.unassignedpercentage.trim()) || 0
    const taskTerminatedPercentage = parseFloat(workersPercentage?.dataset.taskterminatedpercentage.trim()) || 0
    const projectTerminatedPercentage = parseFloat(workersPercentage?.dataset.projectterminatedpercentage.trim()) || 0

    let [labels, cdata, backgroundColor] = []
    let countData = []

    const max = assignedCount + unassignedCount + taskTerminatedCount + projectTerminatedCount
    // Handle case where all counts are zero
    if (max === 0) {
        labels = ['No Data']
        cdata = [1]
        backgroundColor = ['#d3d3d3']
        countData = [0]
    } else {
        labels = [
            `Assigned (${assignedPercentage}%)`,
            `Unassigned (${unassignedPercentage}%)`,
            `Task Terminated (${taskTerminatedPercentage}%)`,
            `Project Terminated (${projectTerminatedPercentage}%)`
        ]
        cdata = [assignedPercentage, unassignedPercentage, taskTerminatedPercentage, projectTerminatedPercentage]
        countData = [assignedCount, unassignedCount, taskTerminatedCount, projectTerminatedCount]
        backgroundColor = ['#38ff5d', '#007bff', '#ffb61e', '#ff5733']
    }

    const DATA_COUNT = (max === 0) ? 1 : 4;

    const data = {
        labels: labels,
        datasets: [
            {
                label: 'Workers',
                data: cdata,
                backgroundColor: backgroundColor,
            }
        ]
    }

    const config = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 25,
                        boxWidth: 12,

                    }
                },
                title: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const count = countData[context.dataIndex]
                            return `Count: ${count} workers`
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
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 300,
                easing: 'linear'
            }
        }
    }

    // Initialize and render the chart
    const ctx = canvas.getContext('2d')
    const workerChart = new Chart(ctx, config)
})