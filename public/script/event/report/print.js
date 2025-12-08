import { Loader } from '../../render/loader.js'

document.addEventListener('DOMContentLoaded', () => {
    const printReportButton = document.getElementById('print_report_button')
    if (!printReportButton) {
        console.warn('Print Report button not found.')
        return
    }

    // Handle print button click
    printReportButton.addEventListener('click', e => {
        e.preventDefault()
        preparePrint()
    })

    // Listen for Ctrl+P or Cmd+P to trigger print preparation
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault()
            preparePrint()
        }
    })

    function preparePrint() {
        Loader.patch(printReportButton.firstElementChild)

        const screenWidth = window.innerWidth
        if (screenWidth < 1024) {
            document.querySelector('meta[name="viewport"]').setAttribute(
                'content',
                'width=1024'
            )
        }

        // Delay to allow charts to resize and convert to images
        setTimeout(() => {
            window.print()

            if (screenWidth < 1024) {
                document.querySelector('meta[name="viewport"]').setAttribute(
                    'content',
                    'width=device-width, initial-scale=1.0'
                )
            }

            Loader.delete()
        }, 500)
    }
})
