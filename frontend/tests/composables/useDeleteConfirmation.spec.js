import { beforeEach, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { api } from '@/services/http'
import { useAsyncAction } from '@/composables/useAsyncAction'
import { useDocuments } from '@/composables/useDocuments'

vi.mock('@/services/http', () => ({ api: vi.fn() }))
beforeEach(() => vi.clearAllMocks())

it('requires explicit confirmation and preserves a company reader with the same ID', async () => {
    const actions = useAsyncAction(() => {})
    const refresh = vi.fn()
    const documents = useDocuments({ ...actions, detail: ref(null), refreshAttachments: refresh })
    documents.deleteAttachment({ id: 1, name: 'Edital' })
    expect(api).not.toHaveBeenCalled()
    documents.cancelDelete()
    expect(documents.pendingDelete.value).toBeNull()
    documents.deleteAttachment({ id: 1, name: 'Edital' })
    api.mockResolvedValueOnce(null)
    await documents.confirmDelete()
    expect(api).toHaveBeenCalledWith('tender-documents/1', { method: 'DELETE' })
    expect(documents.pendingDelete.value).toBeNull()
    expect(refresh).toHaveBeenCalledOnce()
})

it('keeps the confirmation open when deletion fails', async () => {
    const actions = useAsyncAction(() => {})
    const documents = useDocuments({ ...actions, detail: ref(null), refreshAttachments: vi.fn() })
    documents.deleteAttachment({ id: 2, name: 'Edital' })
    api.mockRejectedValueOnce({ status: 409, message: 'Aguarde a busca de anexos' })
    await documents.confirmDelete()
    expect(documents.pendingDelete.value.id).toBe(2)
    expect(actions.error.value).toBe('Aguarde a busca de anexos')
})
