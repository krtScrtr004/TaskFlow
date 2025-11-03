const apiUrl = 'http://localhost/TaskFlow/endpoint/'

/**
 * Http utility module for making HTTP requests with error handling and CSRF protection.
 *
 * Provides methods for GET, POST, PUT, PATCH, and DELETE requests. Handles JSON serialization,
 * CSRF token inclusion, and error parsing for JSON and non-JSON responses.
 *
 * @namespace Http
 *
 * @function GET
 * @memberof Http
 * @description Sends a GET request to the specified endpoint.
 * @param {string} endpoint The API endpoint to request.
 * @returns {Promise<Object|boolean>} Resolves with parsed JSON response, or true for 204/302 status.
 * @throws {Error} Throws error if the request fails or response is not JSON.
 *
 * @function POST
 * @memberof Http
 * @description Sends a POST request to the specified endpoint.
 * @param {string} endpoint The API endpoint to request.
 * @param {Object|FormData|null} [body=null] The request body. Serialized as JSON by default.
 * @param {boolean} [serialize=true] Whether to serialize the body as JSON. Set to false for FormData.
 * @returns {Promise<Object|boolean>} Resolves with parsed JSON response, or true for 204/302 status.
 * @throws {Error} Throws error if the request fails or response is not JSON.
 *
 * @function PUT
 * @memberof Http
 * @description Sends a PUT request to the specified endpoint.
 * @param {string} endpoint The API endpoint to request.
 * @param {Object|FormData|null} [body=null] The request body. Serialized as JSON by default.
 * @param {boolean} [serialize=true] Whether to serialize the body as JSON. Set to false for FormData.
 * @returns {Promise<Object|boolean>} Resolves with parsed JSON response, or true for 204/302 status.
 * @throws {Error} Throws error if the request fails or response is not JSON.
 *
 * @function PATCH
 * @memberof Http
 * @description Sends a PATCH request to the specified endpoint.
 * @param {string} endpoint The API endpoint to request.
 * @param {Object|FormData|null} [body=null] The request body. Serialized as JSON by default.
 * @param {boolean} [serialize=true] Whether to serialize the body as JSON. Set to false for FormData.
 * @returns {Promise<Object|boolean>} Resolves with parsed JSON response, or true for 204/302 status.
 * @throws {Error} Throws error if the request fails or response is not JSON.
 *
 * @function DELETE
 * @memberof Http
 * @description Sends a DELETE request to the specified endpoint.
 * @param {string} endpoint The API endpoint to request.
 * @returns {Promise<boolean>} Resolves with true if the request succeeds.
 * @throws {Error} Throws error if the request fails.
 */
export const Http = (() => {
    const makeRequest = async (endpoint, method = 'GET', body = null, serialize = true) => {
        try {
            const options = {
                method
            }

            if (body !== null && ['POST', 'PUT', 'PATCH'].includes(method)) {
                if (serialize) {
                    options.headers = {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('input[type="hidden"]#csrf_token').value
                    }
                    options.body = JSON.stringify(body)
                } else {
                    // Don't set Content-Type for FormData - browser will set it with boundary
                    options.body = body
                }
            }

            const request = await fetch(`${apiUrl}${endpoint}`, options)

            if (!request.ok) {
                const contentType = request.headers.get('Content-Type')
                let errorData

                // Try to parse JSON error response
                if (contentType && contentType.includes('application/json')) {
                    errorData = await request.json()
                } else {
                    errorData = {
                        message: request.statusText || 'HTTP Error'
                    }

                    switch (request.status) {
                        case 401:
                            errorData.message = 'Unauthorized'
                            break
                        case 403:
                            errorData.message = 'Forbidden'
                            break
                        case 404:
                            errorData.message = 'Resource not found'
                            break
                        default:
                            errorData.message = 'HTTP Error'
                    }
                }

                // Create a custom error with the response data
                const error = new Error(errorData.message || 'Authentication failed')
                error.status = request.status
                error.errors = errorData.errors || []
                throw error
            }

            if (request.status === 204 || request.status === 302) {
                return true
            }

            if (method !== 'DELETE') {
                const contentType = request.headers.get('Content-Type')
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Invalid content type in response.')
                }
                return await request.json()
            }

            return true
        } catch (error) {
            throw error
        }
    }

    return {
        GET: (endpoint) => makeRequest(endpoint, 'GET'),
        POST: (endpoint, body = null, serialize = true) => makeRequest(endpoint, 'POST', body, serialize),
        PUT: (endpoint, body = null, serialize = true) => makeRequest(endpoint, 'PUT', body, serialize),
        PATCH: (endpoint, body = null, serialize = true) => makeRequest(endpoint, 'PATCH', body, serialize),
        DELETE: (endpoint) => makeRequest(endpoint, 'DELETE')
    }
})()
