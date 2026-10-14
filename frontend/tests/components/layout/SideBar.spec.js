import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import SideBar from '@/components/layout/SideBar/index.vue'
import { navigation } from '@/config/navigation'

it('exibe a conta e encaminha navegação e saída por eventos', async () => {
    const wrapper = mount(SideBar, {
        props: {
            user: { name: 'Ana', email: 'ana@example.test' },
            dashboard: { open: 2 },
            nav: navigation,
            view: 'dashboard',
        },
    })
    expect(wrapper.text()).toContain('ana@example.test')
    await wrapper.get('nav button').trigger('click')
    expect(wrapper.emitted('navigate')[0]).toEqual(['dashboard'])
    await wrapper.get('[aria-label="Sair"]').trigger('click')
    expect(wrapper.emitted('logout')).toHaveLength(1)
    await wrapper.setProps({ busy: true })
    expect(wrapper.get('[aria-label="Sair"]').element.disabled).toBe(true)
})
