import { toggleElementClass } from '../../../utility/utility.js'

const header = document.querySelector('main.project-form header')
const navLinks = document.querySelectorAll('main.project-form header a[href^="#"]')
const sections = document.querySelectorAll('main.project-form fieldset[id]')
const mainContainer = document.querySelector('main.project-form')

if (!header) console.warn('Scroll Navigation: Header not found.')

if (!navLinks.length) console.warn('Scroll Navigation: No navigation links found.')

if (!mainContainer) console.warn('Scroll Navigation: Main container not found.')

// Handle click navigation
let oldTargetSection = sections[0]
navLinks?.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault()

        // Get target section
        const targetId = link.getAttribute('href').substring(1)
        const newTargetSection = document.getElementById(targetId)

        if (newTargetSection) {
            toggleElementClass(oldTargetSection, ['fade-out'], ['fade-in'])  // Start fade-out animation
            oldTargetSection.addEventListener('animationend', () => {
                toggleElementClass(oldTargetSection, ['no-display'], ['flex-col', 'fade-out']) // Remove flex and fade-out classes

                toggleElementClass(newTargetSection, ['flex-col', 'fade-in'], ['no-display', 'fade-out']) // Show and fade-in new section
                oldTargetSection = newTargetSection // Update old target section reference
            }, { once: true })

            // Update active state
            updateActiveLink(link)
        }
    })
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
