import { expect, it } from 'vitest'
import CompanyDocumentListPage from '@/views/documents/CompanyDocumentListPage.vue'
import { AppFileUpload } from '@/components/forms'
import { button, mountWorkspace } from '../../helpers/workspace'

it('baixa documentos por ação autenticada e não por link direto', async () => {
    const { wrapper, state } = mountWorkspace(CompanyDocumentListPage)
    const document = { id: 8, name: 'rrt.pdf', kind: 'rrt', status: 'ready', pages: [] }
    state.documents.value = [document]
    await wrapper.vm.$nextTick()
    expect(state.downloadDocument).not.toHaveBeenCalled()
    expect(wrapper.find('a[href*="/download"]').exists()).toBe(false)
    await button(wrapper, 'Baixar').trigger('click')
    expect(state.downloadDocument).toHaveBeenCalledWith(document)
    await button(wrapper, 'Ler texto').trigger('click')
    expect(state.readDocument).toHaveBeenCalledWith(8)
})

it('seleciona o PDF e submete upload somente por formulário', async () => {
    const { wrapper, state } = mountWorkspace(CompanyDocumentListPage)
    const file = new File(['%PDF-test'], 'acervo.pdf', { type: 'application/pdf' })
    Object.defineProperty(wrapper.get('input[type="file"]').element, 'files', { value: [file] })
    await wrapper.get('input[type="file"]').trigger('change')
    expect(state.chosenFile.value).toBe(file)
    expect(state.upload).not.toHaveBeenCalled()
    await wrapper.get('form').trigger('submit')
    expect(state.upload).toHaveBeenCalledOnce()
})

it('exibe a rejeição de arquivo e permite limpar a seleção pelo componente', async () => {
    const { wrapper, state } = mountWorkspace(CompanyDocumentListPage)
    wrapper.findComponent(AppFileUpload).vm.$emit('reject', { reason: 'max-file-size' })
    expect(state.error.value).toContain('20 MB')
    const file = new File(['PDF'], 'test.pdf', { type: 'application/pdf' })
    state.chosenFile.value = file
    await wrapper.vm.$nextTick()
    await button(wrapper, 'Remover arquivo').trigger('click')
    expect(state.chosenFile.value).toBeNull()
    expect(state.upload).not.toHaveBeenCalled()
})
