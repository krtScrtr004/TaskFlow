/**
 * Makes the given element stick to the top of the viewport when scrolling past its original position.
 *
 * This function attaches a scroll event listener to the window and updates the element's
 * CSS `position` and `top` properties to create a "sticky" effect. When the page is scrolled
 * beyond the element's initial offset from the top, it becomes fixed at the top of the viewport.
 * Otherwise, it reverts to its original positioning.
 *
 * @param {HTMLElement} element The DOM element to make sticky. Must be positioned absolutely or statically in the document flow.
 *
 * @example
 * // Make a header element sticky
 * const header = document.getElementById('header');
 * stickToTop(header);
 */
export function stickToTop(element) {
    const stickyStart = element.offsetTop

    function handleScroll() {
        const scrollY = window.scrollY;
        if (scrollY >= stickyStart) {
            element.style.position = 'fixed';
            element.style.top = '0';
        } else {
            element.style.position = 'absolute';
            element.style.top = 'unset'; // Original top position
        }
    }
    window.addEventListener('scroll', handleScroll);

    // Trigger the logic immediately in case the page is already scrolled
    handleScroll();
}