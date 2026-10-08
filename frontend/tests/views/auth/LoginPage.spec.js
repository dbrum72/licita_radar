import { expect, it } from 'vitest'
import LoginPage from '@/views/auth/LoginPage.vue'
import { button, mountWorkspace } from '../../helpers/workspace'

it('envia credenciais somente quando o formulário é submetido', async () => {
    const { wrapper, state } = mountWorkspace(LoginPage)
    expect(state.signIn).not.toHaveBeenCalled()
    await wrapper.get('input[type="email"]').setValue('ana@example.test')
    await wrapper.get('input[type="password"]').setValue('senha-exemplo')
    await wrapper.get('form').trigger('submit')
    expect(state.login.email).toBe('ana@example.test')
    expect(state.login.password).toBe('senha-exemplo')
    expect(state.signIn).toHaveBeenCalledOnce()
})

it('apresenta configuração inicial e bloqueia o botão durante espera', async () => {
    const { wrapper, state } = mountWorkspace(LoginPage)
    state.setup.value = true
    state.isCoolingDown.mockReturnValue(true)
    state.error.value = 'Aguarde antes de tentar novamente.'
    await wrapper.vm.$nextTick()
    expect(wrapper.get('input[autocomplete="name"]').exists()).toBe(true)
    expect(wrapper.get('input[type="password"]').attributes('minlength')).toBe('12')
    expect(wrapper.get('[role="alert"]').text()).toContain('Aguarde')
    expect(button(wrapper, 'Criar acesso').element.disabled).toBe(true)
})
