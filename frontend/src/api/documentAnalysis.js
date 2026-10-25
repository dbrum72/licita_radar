import http from '@/services/http'

export const documentAnalysisApi = {
    get: async (id) => (await http(`opportunities/${id}/analysis`)).data,
    generate: async (id) =>
        (await http(`opportunities/${id}/analysis`, { method: 'POST', timeout: 0 })).data,
    download: async (id) =>
        (await http(`opportunities/${id}/analysis/download`, { responseType: 'blob' })).data,
    source: async (id, key) =>
        (
            await http(`opportunities/${id}/analysis/sources/${encodeURIComponent(key)}`, {
                responseType: 'blob',
            })
        ).data,
}
