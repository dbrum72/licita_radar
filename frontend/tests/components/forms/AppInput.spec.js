import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppInput from '@/components/forms/fields/AppInput/index.vue'

describe('AppInput', () => {
    function mountComponent(props = {}) {
        return mount(AppInput, {
            props: {
                modelValue: '',
                id: 'field',
                label: 'Campo',
                ...props,
            },
        })
    }

    it('renderiza o input', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('id')).toBe('field')
        expect(input.attributes('type')).toBe('text')
    })

    it('renderiza a label associada ao controle', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('label').attributes('for')).toBe('field')

        expect(wrapper.get('label').text()).toContain('Campo')
    })

    it('encaminha type e inputmode', () => {
        const wrapper = mountComponent({
            type: 'number',
            inputmode: 'decimal',
        })

        const input = wrapper.get('input')

        expect(input.attributes('type')).toBe('number')
        expect(input.attributes('inputmode')).toBe('decimal')
    })

    it('encaminha placeholder e autocomplete', () => {
        const wrapper = mountComponent({
            placeholder: 'Digite...',
            autocomplete: 'name',
        })

        const input = wrapper.get('input')

        expect(input.attributes('placeholder')).toBe('Digite...')

        expect(input.attributes('autocomplete')).toBe('name')
    })

    it('encaminha maxlength e minlength', () => {
        const wrapper = mountComponent({
            maxlength: 20,
            minlength: 3,
        })

        const input = wrapper.get('input')

        expect(input.attributes('maxlength')).toBe('20')
        expect(input.attributes('minlength')).toBe('3')
    })

    it('encaminha min, max e step', () => {
        const wrapper = mountComponent({
            min: '2026-01-01',
            max: '2026-12-31',
            step: 1,
        })

        const input = wrapper.get('input')

        expect(input.attributes('min')).toBe('2026-01-01')

        expect(input.attributes('max')).toBe('2026-12-31')

        expect(input.attributes('step')).toBe('1')
    })

    it('encaminha valores numéricos em min, max e step', () => {
        const wrapper = mountComponent({
            type: 'number',
            min: 0,
            max: 100,
            step: 0.5,
        })

        const input = wrapper.get('input')

        expect(input.attributes('min')).toBe('0')
        expect(input.attributes('max')).toBe('100')
        expect(input.attributes('step')).toBe('0.5')
    })

    it('encaminha disabled, readonly e required', () => {
        const wrapper = mountComponent({
            disabled: true,
            readonly: true,
            required: true,
        })

        const input = wrapper.get('input')

        expect(input.attributes()).toHaveProperty('disabled')
        expect(input.attributes()).toHaveProperty('readonly')
        expect(input.attributes()).toHaveProperty('required')
    })

    it('encaminha autofocus', () => {
        const wrapper = mountComponent({
            autofocus: true,
        })

        expect(wrapper.get('input').attributes()).toHaveProperty('autofocus')
    })

    it('emite update:modelValue durante a edição', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').setValue('Novo valor')

        expect(wrapper.emitted('update:modelValue')).toEqual([['Novo valor']])
    })

    it('emite string mesmo quando type é number', async () => {
        const wrapper = mountComponent({
            type: 'number',
        })

        await wrapper.get('input').setValue('25')

        expect(wrapper.emitted('update:modelValue')).toEqual([['25']])
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Texto auxiliar.',
        })

        expect(wrapper.text()).toContain('Texto auxiliar.')
    })

    it('renderiza erro e oculta o hint', () => {
        const wrapper = mountComponent({
            hint: 'Texto auxiliar.',
            error: 'Campo inválido.',
        })

        expect(wrapper.text()).toContain('Campo inválido.')

        expect(wrapper.text()).not.toContain('Texto auxiliar.')
    })

    it('associa hint por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Texto auxiliar.',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('field-hint')
    })

    it('associa erro e marca o input como inválido', () => {
        const wrapper = mountComponent({
            error: 'Campo inválido.',
        })

        const input = wrapper.get('input')

        expect(input.attributes('aria-describedby')).toBe('field-error')

        expect(input.attributes('aria-invalid')).toBe('true')
    })

    it('renderiza os slots prepend e append', () => {
        const wrapper = mount(AppInput, {
            props: {
                id: 'amount',
                label: 'Valor',
            },
            slots: {
                prepend: 'R$',
                append: 'BRL',
            },
        })

        expect(wrapper.text()).toContain('R$')
        expect(wrapper.text()).toContain('BRL')
    })
})
