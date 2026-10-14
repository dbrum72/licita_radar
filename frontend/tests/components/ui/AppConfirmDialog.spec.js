import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import AppConfirmDialog from '@/components/ui/AppConfirmDialog/index.vue'

it('emite confirmação e cancelamento explícitos e bloqueia ambos enquanto processa', async () => {
    const wrapper = mount(AppConfirmDialog, {
        props: { open: true, message: 'Excluir arquivo?', confirmLabel: 'Excluir' },
    })
    const buttons = wrapper.findAll('button')
    await buttons.find((button) => button.text() === 'Excluir').trigger('click')
    await buttons.find((button) => button.text() === 'Cancelar').trigger('click')
    expect(wrapper.emitted('confirm')).toHaveLength(1)
    expect(wrapper.emitted('cancel')).toHaveLength(1)
    await wrapper.setProps({ loading: true })
    expect(wrapper.findAll('button').every((button) => button.element.disabled)).toBe(true)
})
