const apiUrl = 'http://localhost/TaskFlow/'

export const Http = (() => {
    const makeRequest = async (endpoint, method = 'GET', body = null, serialize = true) => {
        try {
            const options = {
                method
            }

            if (body !== null && ['POST', 'PUT', 'PATCH'].includes(method)) {
                options.body = serialize ? JSON.stringify(body) : body
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
                        error: request.status === 401 ? 'Unauthorized' : 'Forbidden',
                        message: request.statusText
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
                    throw new Error('Expected JSON, but got non-JSON response')
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
