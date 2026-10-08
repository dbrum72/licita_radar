import { expect, it, vi } from 'vitest'
import { nextTick, ref } from 'vue'
import { useRequirements } from '@/composables/useRequirements'

it('limpa dados e revisão do edital anterior ao trocar de oportunidade ou sair do detalhe', async () => {
    const detail = ref({ opportunity: { id: 1 } })
    const state = useRequirements({ detail, perform: vi.fn() })
    Object.assign(state.newReq, {
        source_document_id: 10,
        source_page: 5,
        description: 'Exigência antiga',
    })
    state.review({ id: 7, description: 'Revisão antiga' })
    detail.value = { opportunity: { id: 2 } }
    await nextTick()
    expect(state.newReq).toEqual({ source_document_id: '', source_page: 1, description: '' })
    expect(state.reviewForm.value).toBeNull()
    state.newReq.description = 'Rascunho'
    detail.value = null
    await nextTick()
    expect(state.newReq.description).toBe('')
})

it('preserva o rascunho ao atualizar o mesmo edital', async () => {
    const detail = ref({ opportunity: { id: 1 } })
    const state = useRequirements({ detail, perform: vi.fn() })
    state.newReq.description = 'Rascunho atual'
    detail.value = { opportunity: { id: 1, documents: [] } }
    await nextTick()
    expect(state.newReq.description).toBe('Rascunho atual')
})
