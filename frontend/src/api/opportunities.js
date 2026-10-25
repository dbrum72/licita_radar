import http from '@/services/http'

export const opportunitiesApi = {
    list: async (query) => (await http('opportunities?' + query)).data,
    show: async (id) => (await http('opportunities/' + id)).data,
    consult: async (profileIds = []) =>
        (await http('sync', { method: 'POST', timeout: 0, data: { profile_ids: profileIds } }))
            .data,
    attachments: async (id) =>
        (await http('opportunities/' + id + '/attachments', { method: 'POST', timeout: 0 })).data,
    bookmark: async (id, bookmarked) =>
        (await http('opportunities/' + id, { method: 'PATCH', data: { bookmarked } })).data,
    discard: async (id, discarded) =>
        (await http('opportunities/' + id + '/discard', { method: 'PATCH', data: { discarded } }))
            .data,
    export: async (ids) =>
        (
            await http('opportunities/export', {
                method: 'POST',
                timeout: 0,
                data: { ids },
                responseType: 'blob',
            })
        ).data,
}
