import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppCheckbox from '@/components/forms/selection/AppCheckbox/index.vue'

describe('AppCheckbox', () => {
    function mountComponent(props = {}) {
        return mount(AppCheckbox, {
            props: {
                modelValue: false,
                id: 'accept',
                label: 'Aceito os termos',
                ...props,
            },
        })
    }

    it('renderiza o checkbox e a label', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')
        const label = wrapper.get('label')

        expect(input.attributes('type')).toBe('checkbox')
        expect(input.attributes('id')).toBe('accept')
        expect(label.attributes('for')).toBe('accept')
        expect(label.text()).toContain('Aceito os termos')
    })

    it('reflete modelValue no estado checked', () => {
        const wrapper = mountComponent({
            modelValue: true,
        })

        expect(wrapper.get('input').element.checked).toBe(true)
    })

    it('emite update:modelValue ao alterar', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[true]])
    })

    it('renderiza indicador obrigatório', () => {
        const wrapper = mountComponent({
            required: true,
        })

        const required = wrapper.get('.app-checkbox__required')

        expect(required.text()).toBe('*')
        expect(required.attributes('aria-hidden')).toBe('true')
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Marque para continuar.',
        })

        expect(wrapper.text()).toContain('Marque para continuar.')

        expect(wrapper.get('.app-checkbox__hint').attributes('id')).toBe('accept-hint')
    })

    it('renderiza mensagem de erro', () => {
        const wrapper = mountComponent({
            error: 'Aceite obrigatório.',
        })

        expect(wrapper.text()).toContain('Aceite obrigatório.')

        expect(wrapper.get('.app-checkbox__error').attributes('id')).toBe('accept-error')
    })

    it('oculta hint quando existe erro', () => {
        const wrapper = mountComponent({
            hint: 'Marque para continuar.',
            error: 'Aceite obrigatório.',
        })

        expect(wrapper.find('.app-checkbox__hint').exists()).toBe(false)

        expect(wrapper.text()).toContain('Aceite obrigatório.')
    })

    it('associa hint ao controle por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Marque para continuar.',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('accept-hint')
    })

    it('associa erro ao controle e marca como inválido', () => {
        const wrapper = mountComponent({
            error: 'Aceite obrigatório.',
        })

        const input = wrapper.get('input')

        expect(input.attributes('aria-describedby')).toBe('accept-error')

        expect(input.attributes('aria-invalid')).toBe('true')
    })

    it('aplica classe checked', () => {
        const wrapper = mountComponent({
            modelValue: true,
        })

        expect(wrapper.get('.app-checkbox').classes()).toContain('app-checkbox--checked')
    })

    it('aplica classe invalid', () => {
        const wrapper = mountComponent({
            error: 'Aceite obrigatório.',
        })

        expect(wrapper.get('.app-checkbox').classes()).toContain('app-checkbox--invalid')
    })

    it('aplica classe disabled', () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        expect(wrapper.get('.app-checkbox').classes()).toContain('app-checkbox--disabled')

        expect(wrapper.get('input').attributes()).toHaveProperty('disabled')
    })

    it('aplica classe indeterminate e estado nativo', () => {
        const wrapper = mountComponent({
            indeterminate: true,
        })

        expect(wrapper.get('.app-checkbox').classes()).toContain('app-checkbox--indeterminate')

        expect(wrapper.get('input').element.indeterminate).toBe(true)
    })

    it('encaminha required e autofocus', () => {
        const wrapper = mountComponent({
            required: true,
            autofocus: true,
        })

        const input = wrapper.get('input')

        expect(input.attributes()).toHaveProperty('required')

        expect(input.attributes()).toHaveProperty('autofocus')
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)

        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('não gera ids descritivos sem id', () => {
        const wrapper = mountComponent({
            id: undefined,
            hint: 'Marque para continuar.',
        })

        expect(wrapper.get('.app-checkbox__hint').attributes('id')).toBeUndefined()

        expect(wrapper.get('input').attributes('aria-describedby')).toBeUndefined()
    })
})
