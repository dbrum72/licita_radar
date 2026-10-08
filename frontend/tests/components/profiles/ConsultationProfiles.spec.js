import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import ConsultationProfiles from '@/components/profiles/ConsultationProfiles.vue'

it('seleciona vários perfis por checkbox preservando IDs numéricos e permite limpar', async () => {
    const wrapper = mount(ConsultationProfiles, {
        props: {
            profiles: [
                { id: 2, name: 'Escolas' },
                { id: 5, name: 'Hospitais' },
            ],
            modelValue: [],
            'onUpdate:modelValue': (value) => wrapper.setProps({ modelValue: value }),
        },
    })
    expect(wrapper.text()).toContain('Nenhum perfil selecionado')
    expect(wrapper.text()).toContain('a todos os selecionados')
    await wrapper.get('#consultation-profile-2').setValue(true)
    await wrapper.get('#consultation-profile-5').setValue(true)
    expect(wrapper.props('modelValue')).toEqual([2, 5])
    await wrapper.get('#consultation-profile-2').setValue(false)
    expect(wrapper.props('modelValue')).toEqual([5])
    expect(wrapper.text()).toContain('Editais já consultados não serão reavaliados')
})

it('bloqueia alterações durante consulta e informa ausência de perfis', () => {
    const wrapper = mount(ConsultationProfiles, {
        props: { profiles: [{ id: 1, name: 'Perfil' }], disabled: true },
    })
    expect(wrapper.get('input').element.disabled).toBe(true)
    const empty = mount(ConsultationProfiles)
    expect(empty.text()).toContain('Não há perfis ativos')
})
