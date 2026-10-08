import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import AuthShell from '@/components/public/AuthShell.vue'

it('compõe apresentação pública e conteúdo de autenticação independente', () => {
    const wrapper = mount(AuthShell, {
        slots: { default: '<section class="login-panel">Formulário</section>' },
    })
    expect(wrapper.get('.login-story').text()).toContain('O próximo projeto')
    expect(wrapper.get('.login-panel').text()).toBe('Formulário')
})
