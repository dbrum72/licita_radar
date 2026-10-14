import { api } from '@/services/http'

export const opportunitiesApi = {
    list: (query) => api('opportunities?' + query),
    show: (id) => api('opportunities/' + id),
    consult: (profileIds = []) =>
        api('sync', { method: 'POST', body: { profile_ids: profileIds } }),
    attachments: (id) => api('opportunities/' + id + '/attachments', { method: 'POST' }),
    bookmark: (id, bookmarked) =>
        api('opportunities/' + id, { method: 'PATCH', body: { bookmarked } }),
    discard: (id, discarded) =>
        api('opportunities/' + id + '/discard', { method: 'PATCH', body: { discarded } }),
    export: (ids) =>
        api('opportunities/export', { method: 'POST', body: { ids }, responseType: 'blob' }),
}
