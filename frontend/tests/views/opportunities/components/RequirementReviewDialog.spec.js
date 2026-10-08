import { expect, it } from 'vitest'
import RequirementReviewDialog from '@/views/opportunities/components/RequirementReviewDialog.vue'
import { createWorkspace, mountWorkspace } from '../../../helpers/workspace'

it('exige documento e página para conclusão atendida e conserva revisão explícita', async () => {
    const state = createWorkspace()
    state.reviewForm.value = { description: 'Atestado técnico', status: 'pending', notes: '' }
    state.documents.value = [
        { id: 5, name: 'rrt.pdf', pages: [{ number: 1, text: 'Arquitetura' }] },
    ]
    const { wrapper } = mountWorkspace(RequirementReviewDialog, state)
    expect(state.saveReview).not.toHaveBeenCalled()
    await wrapper.get('select').setValue('met')
    expect(wrapper.findAll('select[required]')).toHaveLength(1)
    expect(wrapper.get('input[type="number"]').attributes('required')).toBeDefined()
    await wrapper.get('form').trigger('submit')
    expect(state.saveReview).toHaveBeenCalledOnce()
})
