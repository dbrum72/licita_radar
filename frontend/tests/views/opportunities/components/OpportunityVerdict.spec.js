import { expect, it } from 'vitest'
import OpportunityVerdict from '@/views/opportunities/components/OpportunityVerdict.vue'
import { button, mountWorkspace } from '../../../helpers/workspace'

it('mantém o parecer preliminar e abre a evidência do acervo na página indicada', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityVerdict)
    state.detail.value.opportunity.company_match.evidence = [
        {
            document_id: 5,
            page: 2,
            name: 'rrt.pdf',
            concepts: ['arquitetura'],
            excerpt: 'Projeto arquitetônico',
        },
    ]
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain('PARECER DOCUMENTAL PRELIMINAR')
    expect(wrapper.text()).toContain('rrt.pdf · página 2')
    await button(wrapper, 'rrt.pdf').trigger('click')
    expect(state.readDocument).toHaveBeenCalledWith(5)
})
