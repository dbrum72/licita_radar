import { expect, it } from 'vitest'
import OpportunityShowPage from '@/views/opportunities/OpportunityShowPage.vue'
import OpportunityVerdict from '@/views/opportunities/components/OpportunityVerdict.vue'
import OpportunityAttachments from '@/views/opportunities/components/OpportunityAttachments.vue'
import { button, mountWorkspace } from '../../helpers/workspace'

it('compõe parecer, anexos e requisitos sem iniciar consultas', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityShowPage)
    expect(wrapper.findComponent(OpportunityVerdict).exists()).toBe(true)
    expect(wrapper.findComponent(OpportunityAttachments).exists()).toBe(true)
    expect(wrapper.text()).not.toContain('Requisitos técnicos e evidências')
    expect(wrapper.text()).toContain('Parecer preliminar')
    expect(state.fetchAttachments).not.toHaveBeenCalled()
    await button(wrapper, 'Voltar às oportunidades').trigger('click')
    expect(state.go).toHaveBeenCalledWith('opportunities')
})
