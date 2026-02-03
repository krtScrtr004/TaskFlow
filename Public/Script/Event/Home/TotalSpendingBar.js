const projectStatistics = document.querySelector('.project-statistics')
const leftSide = projectStatistics?.querySelector('.left')
if (!leftSide) console.warn('Project statistics left side element not found')

const totalSpending = leftSide?.querySelector('.total-spending')
const totalSpendingBar = leftSide?.querySelector('#project_total_spending_bar')
if (totalSpending && totalSpendingBar) {
    const projectBudgetValue = parseFloat(totalSpending.dataset.projectbudget ?? 0)
    const totalSpendingValue = parseFloat(totalSpending.dataset.totalspending ?? 0)

    const projectTotalSpendingBar = leftSide.querySelector('#project_total_spending_bar')
    if (projectTotalSpendingBar && totalSpendingValue > 0) {
        const spendingPercentage = Math.min((totalSpendingValue / projectBudgetValue) * 100, 100)
        // Set total spending bar width dynamically
        projectTotalSpendingBar.style.width = spendingPercentage + '%'
    }
} else {
    console.warn('Total spending elements not found')
}