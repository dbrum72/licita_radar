import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import ActionFeedback from '@/components/ui/ActionFeedback/index.vue'

it('distingue erro, sucesso e processamento e emite fechamento', async () => {
    const wrapper = mount(ActionFeedback, {
        props: { error: 'Falha PNCP', message: 'PDF recebido', busy: true },
    })
    expect(wrapper.get('[role="alert"]').text()).toContain('Falha PNCP')
    expect(wrapper.findAll('[role="status"]')).toHaveLength(2)
    await wrapper.get('[aria-label="Fechar erro"]').trigger('click')
    await wrapper.get('[aria-label="Fechar mensagem"]').trigger('click')
    expect(wrapper.emitted('clear-error')).toHaveLength(1)
    expect(wrapper.emitted('clear-message')).toHaveLength(1)
})
