document.addEventListener('DOMContentLoaded', () => {

    const monthlyCount = document.querySelector('.task-statistics > .monthly-count')

    const canvas = monthlyCount?.querySelector('#task_monthly_count_chart')
    if (!canvas) {
        console.error('Task monthly counts chart canvas not found.')
    }

    const monthlyTaskCount = monthlyCount?.querySelector('.monthly-task-count')
    if (!monthlyTaskCount) {
        console.error('Monthly tasks counts data element not found.')
    }

    // TODO: Load data from from the backend
    const januaryCount = parseInt(monthlyTaskCount?.dataset.january) || 0
    const februaryCount = parseInt(monthlyTaskCount?.dataset.february) || 0
    const marchCount = parseInt(monthlyTaskCount?.dataset.march) || 0
    const aprilCount = parseInt(monthlyTaskCount?.dataset.april) || 0
    const mayCount = parseInt(monthlyTaskCount?.dataset.may) || 0
    const max = Math.max(januaryCount, februaryCount, marchCount, aprilCount, mayCount, 20);

    // TODO: Add dataset for every year
    const labels = ['January', 'February', 'March', 'April', 'May']
    const data = {
        labels: labels,
        datasets: [{
            label: 'Tasks Created',
            data: [januaryCount, februaryCount, marchCount, aprilCount, mayCount],
            fill: false,
            borderColor: '#007bff',
            tension: 0.1
        }]
    }

    const config = {
        type: 'line',
        data: data,
        options: {
            scales: {
                y: {
                    min: 0,
                    max: max
                }
            }
        }
    }

    const ctx = canvas.getContext('2d')
    const taskMonthlyCountsChart = new Chart(ctx, config)

})