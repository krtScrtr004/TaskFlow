const apiUrl = 'http://localhost/TaskFlow/'

export const Http = (() => {
    return {
        GET: async (endpoint) => {
            try {
                const request = await fetch(`${apiUrl}${endpoint}`)
                if (!request.ok) {
                    throw new Error(`HTTP error! Status: ${request.status} ${request.statusText}`);
                }

                if (request.status === 204 || request.status === 302) {
                    return true
                }

                const contentType = request.headers.get('Content-Type')
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Expected JSON, but got non-JSON response')
                }

                return await request.json()
            } catch (error) {
                console.log(error)
            }
        },

        POST: async (endpoint, body = null, serialize = true) => {
            try {
                const request = await fetch(`${apiUrl}${endpoint}`, {
                    method: 'POST',
                    body: (serialize) ? JSON.stringify(body) : body
                })

                if (!request.ok) {
                    throw new Error(`HTTP error! Status: ${request.status} ${request.statusText}`)
                }

                if (request.status === 204 || request.status === 302) {
                    return true
                }

                const contentType = request.headers.get('Content-Type')
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Expected JSON, but got non-JSON response')
                }

                return await request.json()
            } catch (error) {
                console.log(error)
            }
        }
    }
})()