import { api } from '@/services/http'

export const documentAnalysisApi = {
    get: (id) => api(`opportunities/${id}/analysis`),
    generate: (id) => api(`opportunities/${id}/analysis`, { method: 'POST' }),
    download: (id) => api(`opportunities/${id}/analysis/download`, { responseType: 'blob' }),
    source: (id, key) =>
        api(`opportunities/${id}/analysis/sources/${encodeURIComponent(key)}`, {
            responseType: 'blob',
        }),
}
