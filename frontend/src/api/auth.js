import http from '@/services/http'
import { clearToken, setToken } from '@/services/token'

export const authApi = {
    initialize: async () => (await http('auth/status')).data,
    current: async () => (await http('me')).data,
    login: (data) => authenticate('login', data),
    setup: (data) => authenticate('setup', data),
    logout: async () => {
        try {
            return (await http('logout', { method: 'POST', timeout: 0 })).data
        } finally {
            clearToken()
        }
    },
}

async function authenticate(url, credentials) {
    const { data } = await http.post(url, credentials)
    setToken(data.access_token)
    return data
}
