import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import PageContainer from '@/components/layout/PageContainer/index.vue'

it('mantém o conteúdo da página dentro da região principal', () => {
    const wrapper = mount(PageContainer, { slots: { default: '<h1>Acervo da empresa</h1>' } })
    expect(wrapper.get('main h1').text()).toBe('Acervo da empresa')
    expect(wrapper.get('main footer').text()).toContain('Fonte de dados: PNCP')
})
