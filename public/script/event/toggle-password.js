const ICON_PATH = 'asset/image/icon/'

const inputs = document.querySelectorAll(
    '.password-toggle-wrapper > input[type="password"]',
    '.password-toggle-wrapper > input[type="text"]'
)
if (inputs.length > 0) {
    console.warn('Password inputs not found.')
}
inputs?.forEach(input => togglePassword(input))
/**
 * Attaches password visibility toggle functionality and icon display logic to a password input field.
 *
 * This function manages the display of a password visibility toggle icon and toggles the input type
 * between 'password' and 'text' when the icon is clicked. It also handles showing and hiding the icon
 * based on user interactions with the input and icon.
 *
 * - Shows the toggle icon when the input is clicked or hovered.
 * - Hides the toggle icon when the mouse leaves the input or icon.
 * - Toggles the input type and icon image when the icon is clicked.
 *
 * @param {HTMLInputElement} input The password input element to which the toggle functionality will be attached.
 *      The input's parent element must contain a child element with the class 'password-toggle-wrapper'
 *      that contains an <img> element for the toggle icon.
 *
 * @example
 * // HTML structure:
 * // <div>
 * //   <input type="password" id="myPassword" />
 * //   <span class="password-toggle-wrapper">
 * //     <img src="show_w.svg" style="display:none;" />
 * //   </span>
 * // </div>
 * //
 * // JavaScript:
 * togglePassword(document.getElementById('myPassword'));
 */
function togglePassword(input) {
    const icon = input.parentElement.querySelector(
        '.password-toggle-wrapper > img'
    )

    function displayIcon() {
        icon.style.display = 'inline-block';
    }
    function hideIcon() {
        icon.style.display = 'none';
    }

    input.addEventListener('click', displayIcon)
    input.addEventListener('mouseover', displayIcon)
    input.addEventListener('mouseout', hideIcon)

    icon.addEventListener('mouseover', displayIcon)
    icon.addEventListener('mouseout', hideIcon)

    icon.onclick = (e) => {
        e.stopPropagation()
        if (input.type === 'password') {
            input.type = 'text'
            icon.src = `${ICON_PATH}hide_w.svg`
        } else {
            input.type = 'password'
            icon.src = `${ICON_PATH}show_w.svg`
        }
    }
}
