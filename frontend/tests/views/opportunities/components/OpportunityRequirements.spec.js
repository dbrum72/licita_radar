import { expect, it } from 'vitest'
import OpportunityRequirements from '@/views/opportunities/components/OpportunityRequirements.vue'
import { button, mountWorkspace } from '../../../helpers/workspace'

it('abre origem e revisão manual sem aprovar requisitos ao renderizar', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityRequirements)
    const requirement = {
        id: 6,
        description: 'Atestado técnico',
        status: 'pending',
        source_document_id: 3,
        source_page: 2,
        candidates: [],
    }
    state.detail.value.opportunity.requirements = [requirement]
    await wrapper.vm.$nextTick()
    expect(state.saveReview).not.toHaveBeenCalled()
    await button(wrapper, 'Edital · página 2').trigger('click')
    expect(state.readDocument).toHaveBeenCalledWith(3, true)
    await button(wrapper, 'Revisar requisito').trigger('click')
    expect(state.review).toHaveBeenCalledWith(requirement)
})
