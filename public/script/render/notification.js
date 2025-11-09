import { stickToTop } from '../utility/stick-to-top.js'

/**
 * Removes a notification element from the DOM with slide animations.
 *
 * This function handles the removal of a notification by applying slide-down and slide-up
 * animation classes, and then removing the notification wrapper from the DOM after the animation ends.
 * It also ensures that event listeners are properly managed to prevent memory leaks or unexpected behavior
 * when the function is triggered multiple times in quick succession.
 *
 * @param {HTMLElement} notificationWrapper The wrapper element containing the notification to be removed.
 * @param {HTMLElement} notificationContent The content element of the notification that will be animated.
 * @param {number} duration The duration in milliseconds to wait before starting the slide-up animation.
 *
 * @returns {void}
 */
function remove(
    notificationWrapper,
    notificationContent,
    duration
) {
    // Define the handler outside the setTimeout to prevent undefined behaviors 
    // When copy-link button is clicked multiple successively
    function onAnimationEnd(ev) {
        if (ev.animationName === 'slide-up') {
            notificationContent.removeEventListener('animationend', onAnimationEnd)
            notificationWrapper.remove()
        }
    }

    // Clear previous animation classes
    notificationContent.classList.remove('slide-up')
    notificationContent.classList.add('slide-down')

    setTimeout(() => {
        notificationContent.classList.remove('slide-down')
        notificationContent.classList.add('slide-up')

        // Remove existing listeners (optional safety) and add again
        notificationContent.removeEventListener('animationend', onAnimationEnd)
        notificationContent.addEventListener('animationend', onAnimationEnd)
    }, duration)
}

/**
 * Renders a notification message in the specified parent element.
 *
 * This function creates a notification element with a style and background color
 * based on the status, displays the provided message, and automatically removes
 * the notification after the specified duration.
 *
 * @param {boolean} status Determines the notification type:
 *      - true: success (blue background)
 *      - false: error (red background)
 * @param {string} message The message to display inside the notification.
 * @param {number} duration Duration in milliseconds before the notification is removed.
 * @param {HTMLElement} parentElem The DOM element to which the notification will be prepended.
 *
 * @returns {void}
 */
function render(
    status,
    message,
    duration,
    parentElem
) {
    const statusStyle = status ? 'success' : 'error'
    const backgroundColor = status ? 'blue' : 'red'

    const html = `
        <section class="notification-wrapper center-child block absolute ${statusStyle}">
            <div class="${backgroundColor}-bg">
                <p class="white-text">${message}</p>
            </div>
        </section>
        `

    parentElem.insertAdjacentHTML('afterbegin', html)

    const notificationWrapper = document.querySelector('.notification-wrapper')
    const notificationContent = notificationWrapper.querySelector('div')

    stickToTop(notificationWrapper)
    remove(
        notificationWrapper,
        notificationContent,
        duration
    )
}

/**
 * Provides notification utilities for displaying success and error messages.
 *
 * The Notification module exposes methods to render success or error notifications
 * within a specified parent element in the DOM. Each notification can be customized
 * with a message and an optional duration.
 *
 * @namespace Notification
 * 
 * @property {Function} success Displays a success notification.
 * @property {Function} error Displays an error notification.
 */

/**
 * Displays a success notification message.
 *
 * Renders a success notification with the provided message and duration inside the specified parent element.
 *
 * @param {string} message The message to display in the notification.
 * @param {number} duration The duration (in milliseconds) for which the notification should be visible.
 * @param {Element} [parentElem=document.querySelector('body')] The parent DOM element to which the notification will be appended.
 */

/**
 * Displays an error notification message.
 *
 * Renders an error notification with the provided message and duration inside the specified parent element.
 *
 * @param {string} message The message to display in the notification.
 * @param {number} duration The duration (in milliseconds) for which the notification should be visible.
 * @param {Element} [parentElem=document.querySelector('body')] The parent DOM element to which the notification will be appended.
 */
export const Notification = (() => {
    return {
        success: function (
            message,
            duration,
            parentElem = document.querySelector('body')
        ) {
            render(
                true,
                message,
                duration,
                parentElem
            )
        },

        error: function (
            message,
            duration,
            parentElem = document.querySelector('body')
        ) {
            render(
                false,
                message,
                duration,
                parentElem
            )
        }
    }
})()
