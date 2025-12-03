import { Loader } from '../../render/loader.js'
import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { validateInputs, userValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const concernForm = document.querySelector('#concern_form')
if (!concernForm) {
    console.warn('Concern form element not found.')
}
concernForm.addEventListener('submit', debounceAsync(submit, 300))

const sendButton = concernForm.querySelector('#send_button')
if (!sendButton) {
    console.warn('Send button element not found.')
}
sendButton.addEventListener('click', debounceAsync(submit, 300))

/**
 * Handles submission of the "concern" form.
 *
 * This async submit handler coordinates UI state, validation, and backend communication:
 * - Prevents default form submission (e.preventDefault()).
 * - Shows a loading/patch state on the send button using Loader.patch.
 * - Reads input values from the form fields with IDs: #user_name, #user_email, #message.
 * - Validates the collected inputs using validateInputs(...) together with userValidationRules(); if validation fails, the submission is aborted.
 * - Sends the validated data to the backend via sendToBackend(name, email, message).
 * - On successful send, presents a success dialog using Dialog.operationSuccess and resets the form (concernForm.reset()).
 * - Catches runtime errors and delegates them to handleException(error).
 * - Ensures Loader.delete() is always called in a finally block to clear loading state.
 *
 * @param {Event} e Submit event fired by the form. The event's target is expected to contain the following form controls:
 *      - user_name: HTMLInputElement | HTMLElement with .value() method — Full name input
 *      - user_email: HTMLInputElement | HTMLElement with .value() method — Email input
 *      - message: HTMLTextAreaElement | HTMLElement with .value() method — Concern message input
 *
 * @returns {Promise<void>} Resolves when the submission flow completes (either after successful send or after an error has been handled).
 */
async function submit(e) {
    e.preventDefault()

    try {
        Loader.patch(sendButton.querySelector('.text-w-icon'))        

        const fullNameElem = concernForm.querySelector('#user_name')
        const emailElem = concernForm.querySelector('#user_email')
        const messageElem = concernForm.querySelector('#message')
        
        if (!validateInputs({
            name: fullNameElem.value.trim(),
            email: emailElem.value.trim(),
            message: messageElem.value.trim()
        }, userValidationRules())) return

        await sendToBackend(
            fullNameElem.value.trim(),
            emailElem.value.trim(),
            messageElem.value.trim()
        )

        Dialog.operationSuccess('Concern Sent', 'Your concern has been sent successfully. We will get back to you as soon as possible.')
        concernForm.reset()
    } catch (error) {
        handleException(error)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends a concern message to the backend endpoint.
 *
 * This async function validates inputs, prevents concurrent requests using a shared
 * isLoading flag, trims string values, and posts the payload to the 'about-us/concern'
 * endpoint usingHhttp.post. The isLoading flag is set to true while the request is in
 * progress and reset to false in all cases.
 *
 * @async
 * @param {string} name Sender's full name.
 *      - Required. Leading and trailing whitespace will be trimmed before sending.
 * @param {string} email Sender's email address.
 *      - Required. Leading and trailing whitespace will be trimmed before sending.
 * @param {string} message The concern or message body to send.
 *      - Required. Leading and trailing whitespace will be trimmed before sending.
 *
 * @returns {Promise<void>} Resolves when the request completes successfully.
 *
 * @throws {Error} If a request is already in progress (isLoading is true).
 * @throws {Error} If any of the required parameters (name, email, message) are missing or empty after trimming.
 * @throws {Error} If the server returns no response.
 *
 * @sideeffects
 *      - Reads and mutates a shared isLoading flag to prevent concurrent requests.
 *      - Calls http.post('about-us/concern', { name, email, message }) with trimmed values.
 */
async function sendToBackend(name, email, message) {
    try {
        if (isLoading) {
            console.warn('Request is already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!name || name.trim() === '') {
            throw new Error('Name is required.')
        }

        if (!email || email.trim() === '') {
            throw new Error('Email is required.')
        }

        if (!message || message.trim() === '') {
            throw new Error('Message is required.')
        }

        const response = await Http.POST('about-us/concern', {
            fullName: name.trim(),
            email: email.trim(),
            message: message.trim()
        })
        if (!response) {
            throw new Error('No response from server.')
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}