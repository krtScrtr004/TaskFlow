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

            const targetId = link.getAttribute('href').substring(1)
            const targetSection = document.getElementById(targetId)

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

        if (mainContainer.scrollTop !== headerOriginalPosition) {
            header.classList.add('moved')   
        } else {
            header.classList.remove('moved')
        }

        sections.forEach(section => {
            const sectionTop = section.offsetTop - headerHeight - 50
            const sectionBottom = sectionTop + section.offsetHeight

            if (mainContainer.scrollTop >= sectionTop && mainContainer.scrollTop < sectionBottom) {
                currentSection = section
            }
        })

        if (currentSection) {
            const activeLink = document.querySelector(`main.project-form > header a[href="#${currentSection.id}"]`)
            if (activeLink) {
                updateActiveLink(activeLink)
            }
        }
    })

    function updateActiveLink(activeLink) {
        navLinks.forEach(link => link.classList.remove('active'))
        activeLink.classList.add('active')
    }

    // Set initial active state
    if (navLinks[0]) {
        navLinks[0].classList.add('active')
    }
})
