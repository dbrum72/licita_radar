const key = 'licitaradar.access_token'
let token = ''

export function getToken() {
    try {
        return sessionStorage.getItem(key) || token
    } catch {
        return token
    }
}
export function setToken(value) {
    token = value || ''
    try {
        if (token) sessionStorage.setItem(key, token)
        else sessionStorage.removeItem(key)
    } catch {
        // Memory storage keeps login functional when browser storage is unavailable.
    }
}
export const clearToken = () => setToken('')
