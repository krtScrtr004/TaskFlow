/**
 * Hamburger Menu Toggle Script
 * Toggles the visibility of the sidenav on mobile/tablet devices
 */

document.addEventListener('DOMContentLoaded', () => {
    const sidenav = document.querySelector('.sidenav')
    const smallSidenav = sidenav.querySelector('.small-sidenav')
    const mainSidenav = sidenav.querySelector('.main-sidenav')
    const hamburgerButton = smallSidenav.querySelector('.hamburger-button')
    const main = document.querySelector('main')

    if (!hamburgerButton || !mainSidenav) {
        console.warn('Hamburger menu elements not found')
        return
    }

    const smallSidenavWidth = sidenav.offsetWidth

    // Toggle menu visibility on hamburger click
    hamburgerButton.addEventListener('click', () => {
        const isVisible = mainSidenav.classList.contains('show')

        if (isVisible) {
            // Hide menu
            smallSidenav.classList.remove('hide')
            mainSidenav.classList.remove('show')
            adjustSidenav(false)
            hamburgerButton.setAttribute('aria-expanded', 'false')
        } else {
            // Show menu
            smallSidenav.classList.add('hide')
            mainSidenav.classList.add('show')
            adjustSidenav(true)
            hamburgerButton.setAttribute('aria-expanded', 'true')
        }
    })

    // Close menu when clicking outside on mobile
    document.addEventListener('click', e => {
        const isClickInsideSidenav = sidenav.contains(e.target)
        const isMenuVisible = mainSidenav.classList.contains('show')

        // Only close if menu is open and click is outside sidenav
        if (!isClickInsideSidenav && isMenuVisible && window.innerWidth <= 992) {
            mainSidenav.classList.remove('show')
            smallSidenav.classList.remove('hide')
            adjustSidenav(false)
            hamburgerButton.setAttribute('aria-expanded', 'false')

            // Prevent other click handlers on the clicked element from running
            e.preventDefault()
            e.stopImmediatePropagation()
        }
    }, true) // Use capture so this runs before bubble-phase handlers

    // Close menu when a navigation link is clicked (mobile only)
    const navLinks = mainSidenav.querySelectorAll('a, button')
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                mainSidenav.classList.remove('show')
                smallSidenav.classList.remove('hide')
                adjustSidenav(false)
                hamburgerButton.setAttribute('aria-expanded', 'false')
            }
        })
    })

    // Initialize aria attributes
    hamburgerButton.setAttribute('aria-expanded', 'false')
    hamburgerButton.setAttribute('aria-label', 'Toggle navigation menu')

    function adjustSidenav(isExpanded) {
        sidenav.classList.toggle('absolute', isExpanded)
        sidenav.classList.toggle('sticky', !isExpanded)
        main.style.marginLeft = isExpanded ? `${smallSidenavWidth}px` : '0'
    }
})
