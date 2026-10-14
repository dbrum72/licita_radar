import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import HeaderBar from '@/components/layout/HeaderBar/index.vue'

it('atualiza o título da área mantendo a identificação do usuário', async () => {
    const wrapper = mount(HeaderBar, { props: { user: { name: 'Ana' }, title: 'Painel' } })
    expect(wrapper.get('strong').text()).toBe('Painel')
    expect(wrapper.get('.avatar').text()).toBe('A')
    await wrapper.setProps({ title: 'Oportunidades' })
    expect(wrapper.get('strong').text()).toBe('Oportunidades')
})
