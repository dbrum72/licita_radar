import { expect, it } from 'vitest'
import DeleteAttachmentDialog from '@/views/opportunities/components/DeleteAttachmentDialog.vue'
import { button, createWorkspace, mountWorkspace } from '../../../helpers/workspace'

it('identifica o arquivo e exige confirmação explícita, exibindo falhas', async () => {
    const state = createWorkspace()
    state.pendingDelete.value = { id: 1, name: 'edital.pdf' }
    state.error.value = 'Não foi possível excluir.'
    const { wrapper } = mountWorkspace(DeleteAttachmentDialog, state)
    expect(wrapper.text()).toContain('edital.pdf')
    expect(wrapper.get('[role="alert"]').text()).toContain('Não foi possível excluir')
    expect(state.confirmDelete).not.toHaveBeenCalled()
    await button(wrapper, 'Cancelar').trigger('click')
    expect(state.cancelDelete).toHaveBeenCalledOnce()
    await button(wrapper, 'Excluir anexo').trigger('click')
    expect(state.confirmDelete).toHaveBeenCalledOnce()
})
