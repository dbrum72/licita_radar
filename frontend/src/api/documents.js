import { api } from '@/services/http'

export const documentsApi = {
    list: () => api('documents'),
    upload: (body) => api('documents', { method: 'POST', body }),
    read: (id, tender = false) => api((tender ? 'tender-documents/' : 'documents/') + id),
    download: (id, tender = false) =>
        api((tender ? 'tender-documents/' : 'documents/') + id + '/download', {
            responseType: 'blob',
        }),
    deleteTender: (id) => api('tender-documents/' + id, { method: 'DELETE' }),
}
