import { expect, it } from 'vitest'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { mountWorkspace } from '../helpers/workspace'

it('compõe a área autenticada e troca a página preservando feedback e navegação', async () => {
    const { wrapper, state } = mountWorkspace(DefaultLayout)
    expect(wrapper.get('main h1').text()).toContain('Um radar')
    state.view.value = 'documents'
    state.title.value = 'Acervo'
    state.error.value = 'Falha de rede'
    await wrapper.vm.$nextTick()
    expect(wrapper.get('header strong').text()).toBe('Acervo')
    expect(wrapper.get('[role="alert"]').text()).toContain('Falha de rede')
    await wrapper.get('[aria-label="Fechar erro"]').trigger('click')
    expect(state.error.value).toBe('')
    await wrapper.get('[aria-label="Sair"]').trigger('click')
    expect(state.signOut).toHaveBeenCalledOnce()
})
