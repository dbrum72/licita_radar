import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppSelect from '@/components/forms/fields/AppSelect/index.vue'

describe('AppSelect', () => {
    function mountComponent(props = {}) {
        return mount(AppSelect, {
            props: {
                modelValue: null,
                id: 'status',
                label: 'Status',
                placeholder: 'Selecione...',
                options: [
                    {
                        label: 'Ativo',
                        value: 1,
                    },
                    {
                        label: 'Inativo',
                        value: 2,
                    },
                ],
                ...props,
            },
        })
    }

    it('renderiza o select', () => {
        const wrapper = mountComponent()

        const select = wrapper.get('select')

        expect(select.exists()).toBe(true)
        expect(select.attributes('id')).toBe('status')
    })

    it('renderiza a label', () => {
        const wrapper = mountComponent()

        const label = wrapper.get('label')

        expect(label.text()).toBe('Status')
        expect(label.attributes('for')).toBe('status')
    })

    it('renderiza placeholder', () => {
        const wrapper = mountComponent()

        expect(wrapper.text()).toContain('Selecione...')
    })

    it('renderiza todas as opções', () => {
        const wrapper = mountComponent()

        const options = wrapper.findAll('option')

        expect(options).toHaveLength(3)
    })

    it('renderiza optionLabel', () => {
        const wrapper = mountComponent()

        const options = wrapper.findAll('option')

        expect(options[1].text()).toBe('Ativo')
        expect(options[2].text()).toBe('Inativo')
    })

    it('preserva valores numéricos', async () => {
        const wrapper = mountComponent()

        const select = wrapper.get('select')

        await select.setValue('2')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([2])
    })

    it('encaminha disabled e required', () => {
        const wrapper = mountComponent({
            disabled: true,
            required: true,
        })

        const select = wrapper.get('select')

        expect(select.attributes()).toHaveProperty('disabled')
        expect(select.attributes()).toHaveProperty('required')
    })

    it('emite focus e blur', async () => {
        const wrapper = mountComponent()

        const select = wrapper.get('select')

        await select.trigger('focus')
        await select.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('preserva valores string', async () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Administrador',
                    value: 'admin',
                },
                {
                    label: 'Usuário',
                    value: 'user',
                },
            ],
        })

        const select = wrapper.get('select')

        await select.setValue('user')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual(['user'])
    })

    it('preserva valores booleanos', async () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Sim',
                    value: true,
                },
                {
                    label: 'Não',
                    value: false,
                },
            ],
        })

        const select = wrapper.get('select')

        await select.setValue('false')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([false])
    })

    it('aceita opções primitivas', async () => {
        const wrapper = mountComponent({
            options: ['Aberto', 'Fechado'],
        })

        const select = wrapper.get('select')

        await select.setValue('Fechado')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual(['Fechado'])
    })

    it('aceita optionLabel e optionValue personalizados', async () => {
        const wrapper = mountComponent({
            optionLabel: 'name',
            optionValue: 'id',
            options: [
                {
                    id: 10,
                    name: 'Primeira opção',
                },
                {
                    id: 20,
                    name: 'Segunda opção',
                },
            ],
        })

        const select = wrapper.get('select')
        const options = wrapper.findAll('option')

        expect(options[1].text()).toBe('Primeira opção')
        expect(options[2].text()).toBe('Segunda opção')

        await select.setValue('20')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([20])
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma opção.',
        })

        expect(wrapper.text()).toContain('Selecione uma opção.')
    })

    it('renderiza mensagem de erro', () => {
        const wrapper = mountComponent({
            error: 'Seleção obrigatória.',
        })

        expect(wrapper.text()).toContain('Seleção obrigatória.')
    })

    it('oculta o hint quando existe erro', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma opção.',
            error: 'Seleção obrigatória.',
        })

        expect(wrapper.text()).toContain('Seleção obrigatória.')
        expect(wrapper.text()).not.toContain('Selecione uma opção.')
    })

    it('associa hint ao select por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma opção.',
        })

        expect(wrapper.get('select').attributes('aria-describedby')).toBe('status-hint')
    })

    it('associa erro ao select por aria-describedby', () => {
        const wrapper = mountComponent({
            error: 'Seleção obrigatória.',
        })

        expect(wrapper.get('select').attributes('aria-describedby')).toBe('status-error')
    })

    it('marca o select como inválido quando há erro', () => {
        const wrapper = mountComponent({
            error: 'Seleção obrigatória.',
        })

        expect(wrapper.get('select').attributes('aria-invalid')).toBe('true')
    })

    it('encaminha autofocus', () => {
        const wrapper = mountComponent({
            autofocus: true,
        })

        expect(wrapper.get('select').attributes()).toHaveProperty('autofocus')
    })
})
