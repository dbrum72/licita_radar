import http from '@/services/http'

export const documentsApi = {
    list: async () => (await http('documents')).data,
    upload: async (data) => (await http('documents', { method: 'POST', timeout: 0, data })).data,
    download: async (id, tender = false) =>
        (
            await http((tender ? 'tender-documents/' : 'documents/') + id + '/download', {
                responseType: 'blob',
            })
        ).data,
    deleteTender: async (id) => (await http('tender-documents/' + id, { method: 'DELETE' })).data,
}
