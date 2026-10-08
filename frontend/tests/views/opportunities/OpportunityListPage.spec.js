import { expect, it } from 'vitest'
import OpportunityListPage from '@/views/opportunities/OpportunityListPage.vue'
import { button, mountWorkspace } from '../../helpers/workspace'

it('aplica filtros e navega entre páginas somente sob solicitação', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityListPage)
    await wrapper.get('input[placeholder="Ex.: projeto arquitetônico"]').setValue('arquitetura')
    expect(state.loadResults).not.toHaveBeenCalled()
    await wrapper.get('form').trigger('submit')
    expect(state.filters.keyword).toBe('arquitetura')
    expect(state.loadResults).toHaveBeenCalledOnce()
    state.results.value.last_page = 2
    await wrapper.vm.$nextTick()
    await button(wrapper, 'Próxima').trigger('click')
    expect(state.loadResults).toHaveBeenLastCalledWith(2)
})

it('respeita limite de seleção e diferencia restaurar de descartar', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityListPage)
    state.results.value.data = [
        {
            id: 99,
            object: 'Projeto',
            scope_status: 'relevant',
            discarded: false,
            company_match: { evidence: [] },
        },
    ]
    state.selectedIds.value = Array.from({ length: 50 }, (_, id) => id)
    await wrapper.vm.$nextTick()
    expect(wrapper.get('.export-choice input').element.disabled).toBe(true)
    await button(wrapper, 'Analisar edital').trigger('click')
    expect(state.openDetail).toHaveBeenCalledWith(99)
    state.results.value.data[0].discarded = true
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.export-choice').exists()).toBe(false)
    await button(wrapper, 'Restaurar').trigger('click')
    expect(state.toggleDiscard).toHaveBeenCalledWith(state.results.value.data[0])
})

it('permite consultar salvas ou descartadas separadamente e explica o acúmulo de pendentes', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityListPage)
    expect(wrapper.text()).toContain('pendentes de consultas anteriores permanecem na lista')
    await wrapper.get('#opportunitylistpage-9').setValue(true)
    expect(state.filters.bookmarked).toBe(true)
    expect(state.filters.discarded).toBe(false)
    await wrapper.get('#opportunitylistpage-10').setValue(true)
    expect(state.filters.discarded).toBe(true)
    expect(state.filters.bookmarked).toBe(false)
})
