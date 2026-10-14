import { api } from '@/services/http'
import { clearToken } from '@/services/token'

export const authApi = {
    initialize: () => api('auth/status', { timeoutMs: 15000 }),
    current: () => api('me', { timeoutMs: 15000 }),
    login: (body) => api('login', { method: 'POST', body }),
    setup: (body) => api('setup', { method: 'POST', body }),
    logout: async () => {
        try {
            return await api('logout', { method: 'POST' })
        } finally {
            clearToken()
        }
    },
}
