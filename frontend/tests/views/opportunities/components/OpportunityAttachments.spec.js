import { expect, it } from 'vitest'
import OpportunityAttachments from '@/views/opportunities/components/OpportunityAttachments.vue'
import { button, mountWorkspace } from '../../../helpers/workspace'

it('busca anexos somente por botão e respeita o bloqueio de processamento', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityAttachments)
    expect(state.fetchAttachments).not.toHaveBeenCalled()
    await button(wrapper, 'Buscar anexos').trigger('click')
    expect(state.fetchAttachments).toHaveBeenCalledOnce()
    state.busy.value = true
    await wrapper.vm.$nextTick()
    expect(button(wrapper, 'Buscar anexos').element.disabled).toBe(true)
})

it('diferencia documentos do edital e solicita confirmação antes de excluir', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityAttachments)
    const doc = { id: 4, name: 'edital.pdf', status: 'ready' }
    state.detail.value.opportunity.documents = [doc]
    await wrapper.vm.$nextTick()
    await button(wrapper, 'Ler páginas').trigger('click')
    expect(state.readDocument).toHaveBeenCalledWith(4, true)
    await button(wrapper, 'Excluir anexo').trigger('click')
    expect(state.deleteAttachment).toHaveBeenCalledWith(doc)
    expect(state.confirmDelete).not.toHaveBeenCalled()
})
