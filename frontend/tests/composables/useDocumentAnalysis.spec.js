import { beforeEach, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { api } from '@/services/http'
import { useAsyncAction } from '@/composables/useAsyncAction'
import { useDocumentAnalysis } from '@/composables/useDocumentAnalysis'

vi.mock('@/services/http', () => ({ api: vi.fn() }))
beforeEach(() => vi.clearAllMocks())

function setup() {
    const actions = useAsyncAction(() => {})
    const detail = ref({ opportunity: { id: 4 }, analysis: { configured: true, report: null } })
    return { ...actions, detail, ...useDocumentAnalysis({ ...actions, detail }) }
}

it('gera somente sob solicitação e reconhece o reaproveitamento sem nova cobrança', async () => {
    const state = setup()
    expect(api).not.toHaveBeenCalled()
    api.mockResolvedValue({ configured: true, report: { analysis: {} }, cached: true })
    await state.generateAnalysis()
    expect(api).toHaveBeenCalledWith('opportunities/4/analysis', { method: 'POST' })
    expect(state.analysis.value.report).toBeTruthy()
    expect(state.message.value).toContain('Nenhuma nova chamada')
    expect(state.generatingAnalysis.value).toBe(false)
})

it('não aplica um resultado tardio à oportunidade que o usuário abriu depois', async () => {
    const state = setup()
    let resolve
    api.mockReturnValue(
        new Promise((done) => {
            resolve = done
        }),
    )
    const pending = state.generateAnalysis()
    state.detail.value = { opportunity: { id: 5 }, analysis: { configured: true, report: null } }
    resolve({ report: { analysis: {} } })
    await pending
    expect(state.detail.value.analysis.report).toBeNull()
})

it('preserva o relatório ao falhar e atualiza por GET sem repetir a geração', async () => {
    const state = setup()
    state.detail.value.analysis.report = { analysis: { summary: 'Anterior' } }
    api.mockRejectedValueOnce(new Error('IA indisponível'))
    await state.generateAnalysis()
    expect(state.error.value).toBe('IA indisponível')
    expect(state.analysis.value.report.analysis.summary).toBe('Anterior')
    api.mockResolvedValueOnce({ configured: true, report: { analysis: { summary: 'Atual' } } })
    await state.refreshAnalysis()
    expect(api).toHaveBeenLastCalledWith('opportunities/4/analysis')
    expect(state.analysis.value.report.analysis.summary).toBe('Atual')
})
