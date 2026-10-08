import { expect, it } from 'vitest'
import SearchProfileDialog from '@/views/profiles/components/SearchProfileDialog.vue'
import { createWorkspace, mountWorkspace } from '../../../helpers/workspace'

it('edita palavras de exclusão e salva somente ao submeter', async () => {
    const state = createWorkspace()
    state.profileForm.value = {
        name: 'Projetos',
        keywords: '',
        excluded_keywords: '',
        states: '',
        modalities: [],
        min_days: 3,
    }
    const { wrapper } = mountWorkspace(SearchProfileDialog, state)
    expect(state.saveProfile).not.toHaveBeenCalled()
    const label = wrapper
        .findAll('label')
        .find((element) => element.text().includes('Palavras-chave a ignorar'))
    await wrapper.get('#' + label.attributes('for')).setValue('obra, reforma')
    expect(state.profileForm.value.excluded_keywords).toBe('obra, reforma')
    await wrapper.get('form').trigger('submit')
    expect(state.saveProfile).toHaveBeenCalledOnce()
    state.busy.value = true
    await wrapper.vm.$nextTick()
    await wrapper.get('[aria-label="Fechar"]').trigger('click')
    expect(state.profileForm.value).not.toBeNull()
})
