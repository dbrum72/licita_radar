import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import SideBarNav from '@/components/navigation/SideBarNav/index.vue'
import { navigation } from '@/config/navigation'

it('identifica a área ativa no detalhe e emite navegação sem chamar APIs', async () => {
    const wrapper = mount(SideBarNav, {
        props: { items: navigation, activeId: 'detail', opportunityCount: 12 },
    })
    expect(wrapper.get('nav').attributes('aria-label')).toBe('Navegação principal')
    expect(wrapper.get('button.active').text()).toContain('Oportunidades')
    expect(wrapper.get('.nav-count').text()).toBe('12')
    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('navigate')[0]).toEqual(['dashboard'])
    await wrapper.setProps({ busy: true })
    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('navigate')).toHaveLength(1)
})
