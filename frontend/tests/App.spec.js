import { expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import App from '@/App.vue'
import { useLicitaRadar } from '@/composables/useLicitaRadar'
import { createWorkspace } from './helpers/workspace'

vi.mock('@/composables/useLicitaRadar', () => ({ useLicitaRadar: vi.fn() }))

it('transita de carregamento para login e área privada, removendo a área ao sair', async () => {
    const state = createWorkspace()
    state.booting = ref(true)
    state.user.value = null
    useLicitaRadar.mockReturnValue(state)
    const wrapper = mount(App)
    expect(wrapper.text()).toContain('Carregando LicitaRadar')
    expect(wrapper.find('.app-shell').exists()).toBe(false)
    state.booting.value = false
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.login-page').exists()).toBe(true)
    state.user.value = { name: 'Ana', email: 'ana@example.test' }
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.login-page').exists()).toBe(false)
    expect(wrapper.find('.app-shell').exists()).toBe(true)
    state.user.value = null
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.app-shell').exists()).toBe(false)
    expect(wrapper.find('.login-page').exists()).toBe(true)
})
