/**
 * Smooth Scroll Navigation Handler
 * Handles anchor link navigation with offset for sticky header
 */

document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('main.project-form header')
    const navLinks = document.querySelectorAll('main.project-form header a[href^="#"]')
    const sections = document.querySelectorAll('main.project-form fieldset[id]')
    const mainContainer = document.querySelector('main.project-form')

    if (!header) {
        console.warn('Scroll Navigation: Header not found.')
        return
    }

    if (!navLinks.length) {
        console.warn('Scroll Navigation: No navigation links found.')
        return
    }

    if (!mainContainer) {
        console.warn('Scroll Navigation: Main container not found.')
        return
    }

    const headerOriginalPosition = header.offsetTop

    const headerHeight = header.offsetHeight + 20 // Extra padding

    // Handle click navigation
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault()

            // Get target section
            const targetId = link.getAttribute('href').substring(1)
            const targetSection = document.getElementById(targetId)

            // Scroll to target section with offset
            if (targetSection) {
                const targetPosition = targetSection.offsetTop - headerHeight

                mainContainer.scrollTo({
                    top: targetPosition - 100,
                    behavior: 'smooth'
                })

                // Update active state
                updateActiveLink(link)
            }
        })
    })

    // Update active link on scroll
    mainContainer.addEventListener('scroll', () => {
        let currentSection = null

        // Add shadow to header when scrolled
        if (mainContainer.scrollTop !== headerOriginalPosition) {
            header.classList.add('moved')   
        } else {
            header.classList.remove('moved')
        }

        // Determine current section in view
        sections.forEach(section => {
            // Get visible height of the scroll container (fallback to window height)
            const viewportHeight = mainContainer.clientHeight || window.innerHeight
            const threshold = viewportHeight / 2 // Halfway point of viewport

            // Calculate section top position with offset, accounting for header height and threshold
            const sectionTop = section.offsetTop - headerHeight - threshold 

            // Check if scroll position is within section bounds
            if (mainContainer.scrollTop >= sectionTop) {
                currentSection = section
            }
        })

        // Update active link based on current section
        if (currentSection) {
            const activeLink = document.querySelector(`main.project-form header a[href="#${currentSection.id}"]`)
            if (activeLink) {
                updateActiveLink(activeLink)
            }
        }
    })

    /**
     * Marks a navigation link as active.
     *
     * This function removes the 'active' class from every element in the surrounding
     * navLinks collection and then adds the 'active' class to the provided activeLink,
     * updating the visual active state of the navigation UI.
     *
     * @param {Element} activeLink The DOM element (e.g. an <a> or <button>) to mark as active.
     *                             It is expected to be one of the elements contained in the
     *                             navLinks collection available in the enclosing scope.
     * @returns {void}
     */
    function updateActiveLink(activeLink) {
        navLinks.forEach(link => link.classList.remove('active'))
        activeLink.classList.add('active')
    }

    // Set initial active state
    if (navLinks[0]) {
        navLinks[0].classList.add('active')
    }
})
