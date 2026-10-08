import { api } from '@/services/http'

export const dashboardApi = {
    get: () => api('dashboard', { timeoutMs: 15000 }),
}
