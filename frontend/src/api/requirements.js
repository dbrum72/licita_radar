import { api } from '@/services/http'

export const requirementsApi = {
    create: (id, body) => api('opportunities/' + id + '/requirements', { method: 'POST', body }),
    review: (id, body) => api('requirements/' + id, { method: 'PATCH', body }),
}
