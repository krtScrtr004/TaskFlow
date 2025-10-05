const taskStatisticsChart = document.querySelector('.task-statistics-chart');

function createChart(elementId, labels, data, colors, width = 400, height = 400) {
    const chartElement = taskStatisticsChart.querySelector(elementId);
    if (!chartElement) {
        console.error(`Chart element ${elementId} not found!`);
        return;
    }
    
    // Set fixed dimensions for the canvas
    chartElement.width = width;
    chartElement.height = height;
    chartElement.style.width = width + 'px';
    chartElement.style.height = height + 'px';
    
    // Set fixed dimensions for the chart
    new Chart(chartElement, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors
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
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${context.label || ''}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: { animateRotate: true, duration: 1000 }
        }
    });
}

// Status chart
const statusPercentage = taskStatisticsChart.querySelector('.status-percentage');
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
);

// Priority chart
const priorityPercentage = taskStatisticsChart.querySelector('.priority-percentage');
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
);
