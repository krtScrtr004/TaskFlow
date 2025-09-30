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