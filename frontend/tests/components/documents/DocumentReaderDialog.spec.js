import { expect, it } from 'vitest'
import DocumentReaderDialog from '@/components/documents/DocumentReaderDialog.vue'
import { mountWorkspace } from '../../helpers/workspace'

it('exibe páginas e a necessidade de revisão sem iniciar OCR', async () => {
    const { wrapper, state } = mountWorkspace(DocumentReaderDialog)
    state.reading.value = {
        name: 'acervo.pdf',
        status: 'needs_ocr',
        pages: [{ number: 1, text: '' }],
    }
    await wrapper.vm.$nextTick()
    expect(wrapper.get('[role="dialog"]').attributes('aria-labelledby')).toBeTruthy()
    expect(wrapper.text()).toContain('Necessário OCR/revisão')
    expect(wrapper.text()).toContain('Sem texto extraível.')
    await wrapper.get('[aria-label="Fechar"]').trigger('click')
    expect(state.reading.value).toBeNull()
})
