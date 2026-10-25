import http from '@/services/http'

export const dashboardApi = {
    get: async () => (await http('dashboard')).data,
}
