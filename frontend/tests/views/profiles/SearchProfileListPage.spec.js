import { expect, it } from 'vitest'
import SearchProfileListPage from '@/views/profiles/SearchProfileListPage.vue'
import { button, mountWorkspace } from '../../helpers/workspace'

it('envia resumos somente por botão e bloqueia repetição durante espera', async () => {
    const { wrapper, state } = mountWorkspace(SearchProfileListPage)
    expect(state.sendDigest).not.toHaveBeenCalled()
    await button(wrapper, 'Enviar resumos').trigger('click')
    expect(state.sendDigest).toHaveBeenCalledOnce()
    state.busy.value = true
    await wrapper.vm.$nextTick()
    expect(button(wrapper, 'Enviar resumos').element.disabled).toBe(true)
})

it('mostra termos de exclusão e identifica o perfil ao editar ou pausar', async () => {
    const { wrapper, state } = mountWorkspace(SearchProfileListPage)
    const profile = {
        id: 2,
        name: 'Arquitetura',
        active: true,
        excluded_keywords: ['obra'],
        keywords: [],
        states: [],
        min_days: 3,
    }
    state.profiles.value = [profile]
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain('Ignorar: obra')
    await button(wrapper, 'Editar perfil').trigger('click')
    expect(state.editProfile).toHaveBeenCalledWith(profile)
    await button(wrapper, 'Pausar').trigger('click')
    expect(state.toggleProfile).toHaveBeenCalledWith(profile)
})
