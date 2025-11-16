document.addEventListener('DOMContentLoaded', () => {

    const workerStatistics = document.querySelector('.report .worker-statistics')

    const canvas = workerStatistics?.querySelector('#worker_statistics_chart')
    if (!canvas) {
        console.error('Worker statistics chart canvas not found.')
        return
    }

    const workerCountContainer = workerStatistics?.querySelector('.worker-count')
    if (!workerCountContainer) {
        console.error('Worker count container not found in worker statistics section.')
        return
    }

    // Parse worker data from hidden spans
    const workerSpans = workerCountContainer.querySelectorAll('span[data-status]')
    const workerData = {}
    
    workerSpans.forEach(span => {
        const status = span.dataset.status
        const count = parseInt(span.dataset.count) || 0
        const percentage = parseFloat(span.dataset.percentage) || 0
        
        workerData[status.toLowerCase()] = { count, percentage }
    })

    // Extract individual status data
    const assignedCount = workerData['assigned']?.count || 0
    const unassignedCount = workerData['unassigned']?.count || 0
    const terminatedCount = workerData['terminated']?.count || 0

    const assignedPercentage = workerData['assigned']?.percentage || 0
    const unassignedPercentage = workerData['unassigned']?.percentage || 0
    const terminatedPercentage = workerData['terminated']?.percentage || 0

    let labels = []
    let cdata = []
    let backgroundColor = []
    let countData = []

    const totalCount = assignedCount + unassignedCount + terminatedCount
    
    // Handle case where all counts are zero
    if (totalCount === 0) {
        labels = ['No Data']
        cdata = [1]
        backgroundColor = ['#d3d3d3']
        countData = [0]
    } else {
        labels = [
            `Assigned (${assignedPercentage.toFixed(1)}%)`,
            `Unassigned (${unassignedPercentage.toFixed(1)}%)`,
            `Terminated (${terminatedPercentage.toFixed(1)}%)`
        ]
        cdata = [assignedPercentage, unassignedPercentage, terminatedPercentage]
        countData = [assignedCount, unassignedCount, terminatedCount]
        backgroundColor = ['#38ff5d', '#007bff', '#ff5733']
    }

    const DATA_COUNT = (totalCount === 0) ? 1 : 3;

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