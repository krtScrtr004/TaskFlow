const apiUrl = 'http://localhost/TaskFlow/'

export const Http = (() => {
    const makeRequest = async (endpoint, method = 'GET', body = null, serialize = true) => {
        try {
            const options = { 
                method 
            }
            
            if (body !== null && ['POST', 'PUT'].includes(method)) {
                options.body = serialize ? JSON.stringify(body) : body
            }
            
            const request = await fetch(`${apiUrl}${endpoint}`, options)
            
            if (!request.ok) {
                throw new Error(`HTTP error! Status: ${request.status} ${request.statusText}`)
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
            console.log(error)
        }
    }
    
    return {
        GET: (endpoint) => makeRequest(endpoint, 'GET'),
        POST: (endpoint, body = null, serialize = true) => makeRequest(endpoint, 'POST', body, serialize),
        PUT: (endpoint, body = null, serialize = true) => makeRequest(endpoint, 'PUT', body, serialize),
        DELETE: (endpoint) => makeRequest(endpoint, 'DELETE')
    }
})()
