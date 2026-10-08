import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppTextarea from '@/components/forms/fields/AppTextarea/index.vue'

describe('AppTextarea', () => {
    function mountComponent(props = {}) {
        return mount(AppTextarea, {
            props: {
                modelValue: 'Texto inicial',
                id: 'description',
                label: 'Descrição',
                ...props,
            },
        })
    }

    it('renderiza o textarea', () => {
        const wrapper = mountComponent()

        const textarea = wrapper.get('textarea')

        expect(textarea.exists()).toBe(true)
        expect(textarea.attributes('id')).toBe('description')
        expect(textarea.element.value).toBe('Texto inicial')
    })

    it('renderiza a label associada ao controle', () => {
        const wrapper = mountComponent()

        const label = wrapper.get('label')

        expect(label.text()).toBe('Descrição')
        expect(label.attributes('for')).toBe('description')
    })

    it('aplica o número padrão de linhas', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('textarea').attributes('rows')).toBe('4')
    })

    it('encaminha placeholder', () => {
        const wrapper = mountComponent({
            placeholder: 'Digite uma descrição...',
        })

        expect(wrapper.get('textarea').attributes('placeholder')).toBe('Digite uma descrição...')
    })

    it('encaminha maxlength e minlength', () => {
        const wrapper = mountComponent({
            maxlength: 120,
            minlength: 10,
        })

        const textarea = wrapper.get('textarea')

        expect(textarea.attributes('maxlength')).toBe('120')
        expect(textarea.attributes('minlength')).toBe('10')
    })

    it('encaminha rows, cols e wrap', () => {
        const wrapper = mountComponent({
            rows: 8,
            cols: 40,
            wrap: 'hard',
        })

        const textarea = wrapper.get('textarea')

        expect(textarea.attributes('rows')).toBe('8')
        expect(textarea.attributes('cols')).toBe('40')
        expect(textarea.attributes('wrap')).toBe('hard')
    })

    it('encaminha readonly e disabled', () => {
        const wrapper = mountComponent({
            readonly: true,
            disabled: true,
        })

        const textarea = wrapper.get('textarea')

        expect(textarea.attributes()).toHaveProperty('readonly')
        expect(textarea.attributes()).toHaveProperty('disabled')
    })

    it('encaminha required', () => {
        const wrapper = mountComponent({
            required: true,
        })

        expect(wrapper.get('textarea').attributes()).toHaveProperty('required')
    })

    it('emite update:modelValue durante a edição', async () => {
        const wrapper = mountComponent()

        const textarea = wrapper.get('textarea')

        await textarea.setValue('Novo conteúdo')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates).toBeTruthy()
        expect(updates.at(-1)).toEqual(['Novo conteúdo'])
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const textarea = wrapper.get('textarea')

        await textarea.trigger('focus')
        await textarea.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Informe uma descrição objetiva.',
        })

        expect(wrapper.text()).toContain('Informe uma descrição objetiva.')
    })

    it('renderiza mensagem de erro', () => {
        const wrapper = mountComponent({
            error: 'Campo obrigatório.',
        })

        expect(wrapper.text()).toContain('Campo obrigatório.')
    })

    it('oculta o hint quando existe erro', () => {
        const wrapper = mountComponent({
            hint: 'Informe uma descrição objetiva.',
            error: 'Campo obrigatório.',
        })

        expect(wrapper.text()).toContain('Campo obrigatório.')
        expect(wrapper.text()).not.toContain('Informe uma descrição objetiva.')
    })

    it('associa hint ao textarea por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Informe uma descrição objetiva.',
        })

        expect(wrapper.get('textarea').attributes('aria-describedby')).toBe('description-hint')
    })

    it('associa erro ao textarea por aria-describedby', () => {
        const wrapper = mountComponent({
            error: 'Campo obrigatório.',
        })

        expect(wrapper.get('textarea').attributes('aria-describedby')).toBe('description-error')
    })

    it('marca o textarea como inválido quando há erro', () => {
        const wrapper = mountComponent({
            error: 'Campo obrigatório.',
        })

        expect(wrapper.get('textarea').attributes('aria-invalid')).toBe('true')
    })
})
