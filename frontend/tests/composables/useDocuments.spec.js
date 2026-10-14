import { afterEach, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { api } from '@/services/http'
import { useAsyncAction } from '@/composables/useAsyncAction'
import { useDocuments } from '@/composables/useDocuments'

vi.mock('@/services/http', () => ({ api: vi.fn() }))
afterEach(() => {
    vi.restoreAllMocks()
    vi.unstubAllGlobals()
    vi.clearAllMocks()
    vi.useRealTimers()
})

function documents() {
    const actions = useAsyncAction(() => {})
    return {
        ...actions,
        ...useDocuments({ ...actions, detail: ref(null), refreshAttachments: vi.fn() }),
    }
}

it('abre a aba antes da requisição autenticada e exibe o PDF do storage', async () => {
    vi.useFakeTimers()
    const tab = { opener: window, closed: false, location: { replace: vi.fn() }, close: vi.fn() }
    const open = vi.spyOn(window, 'open').mockReturnValue(tab)
    const createObjectURL = vi.fn(() => 'blob:pdf')
    const revokeObjectURL = vi.fn()
    vi.stubGlobal('URL', { createObjectURL, revokeObjectURL })
    let resolve
    api.mockReturnValue(
        new Promise((done) => {
            resolve = done
        }),
    )
    const pending = documents().openDocument({ id: 4 }, true)
    expect(open).toHaveBeenCalledWith('', '_blank')
    expect(tab.opener).toBeNull()
    expect(api).toHaveBeenCalledWith('tender-documents/4/download', { responseType: 'blob' })
    expect(tab.location.replace).not.toHaveBeenCalled()
    resolve(new Blob(['%PDF-original']))
    await pending
    expect(createObjectURL.mock.calls[0][0].type).toBe('application/pdf')
    expect(tab.location.replace).toHaveBeenCalledWith('blob:pdf')
    vi.advanceTimersByTime(60000)
    expect(revokeObjectURL).toHaveBeenCalledWith('blob:pdf')
})

it('fecha a aba vazia e informa a falha de acesso ao arquivo', async () => {
    const tab = { opener: null, close: vi.fn() }
    vi.spyOn(window, 'open').mockReturnValue(tab)
    api.mockRejectedValue(new Error('Arquivo indisponível'))
    const state = documents()
    await state.openDocument({ id: 4 }, true)
    expect(tab.close).toHaveBeenCalledOnce()
    expect(state.error.value).toBe('Arquivo indisponível')
})

it('informa o bloqueio de popup sem requisitar o arquivo', async () => {
    vi.spyOn(window, 'open').mockReturnValue(null)
    const state = documents()
    await state.openDocument({ id: 4 }, true)
    expect(api).not.toHaveBeenCalled()
    expect(state.error.value).toContain('nova aba')
})
