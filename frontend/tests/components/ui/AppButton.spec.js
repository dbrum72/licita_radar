import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppButton from '@/components/ui/AppButton/index.vue'

describe('AppButton', () => {
    function mountComponent(props = {}, slots = {}) {
        return mount(AppButton, {
            props,
            slots: {
                default: 'Salvar',
                ...slots,
            },
        })
    }

    it('renderiza um botão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('button').exists()).toBe(true)
        expect(wrapper.get('button').text()).toBe('Salvar')
    })

    it('utiliza type button por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('button').attributes('type')).toBe('button')
    })

    it('encaminha type submit', () => {
        const wrapper = mountComponent({
            type: 'submit',
        })

        expect(wrapper.get('button').attributes('type')).toBe('submit')
    })

    it('aplica variant primary por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('button').classes()).toContain('btn--primary')
    })

    it('aplica variantes configuradas', () => {
        const wrapper = mountComponent({
            variant: 'accent',
        })

        expect(wrapper.get('button').classes()).toContain('btn--accent')
    })

    it('aplica a variante verde de navegação', () => {
        const wrapper = mountComponent({
            variant: 'navigation',
        })

        expect(wrapper.get('button').classes()).toContain('btn--navigation')
    })

    it('aplica a variante de perigo', () => {
        const wrapper = mountComponent({
            variant: 'danger',
        })

        expect(wrapper.get('button').classes()).toContain('btn--danger')
    })

    it('não aplica classe de tamanho para md', () => {
        const wrapper = mountComponent({
            size: 'md',
        })

        const classes = wrapper.get('button').classes()

        expect(classes).not.toContain('btn--sm')
        expect(classes).not.toContain('btn--lg')
    })

    it('aplica tamanho sm', () => {
        const wrapper = mountComponent({
            size: 'sm',
        })

        expect(wrapper.get('button').classes()).toContain('btn--sm')
    })

    it('aplica tamanho lg', () => {
        const wrapper = mountComponent({
            size: 'lg',
        })

        expect(wrapper.get('button').classes()).toContain('btn--lg')
    })

    it('aplica block', () => {
        const wrapper = mountComponent({
            block: true,
        })

        expect(wrapper.get('button').classes()).toContain('app-button--block')
    })

    it('encaminha disabled', () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        expect(wrapper.get('button').attributes()).toHaveProperty('disabled')
    })

    it('emite click quando habilitado', async () => {
        const wrapper = mountComponent()

        await wrapper.get('button').trigger('click')

        expect(wrapper.emitted('click')).toHaveLength(1)
    })

    it('não emite click quando disabled', async () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        await wrapper.get('button').trigger('click')

        expect(wrapper.emitted('click')).toBeUndefined()
    })

    it('loading desabilita o botão', () => {
        const wrapper = mountComponent({
            loading: true,
        })

        expect(wrapper.get('button').attributes()).toHaveProperty('disabled')
    })

    it('loading aplica aria-busy', () => {
        const wrapper = mountComponent({
            loading: true,
        })

        expect(wrapper.get('button').attributes('aria-busy')).toBe('true')
    })

    it('loading aplica classe de estado', () => {
        const wrapper = mountComponent({
            loading: true,
        })

        expect(wrapper.get('button').classes()).toContain('app-button--loading')
    })

    it('loading renderiza spinner', () => {
        const wrapper = mountComponent({
            loading: true,
        })

        expect(wrapper.find('.app-button__spinner').exists()).toBe(true)
    })

    it('loading oculta o conteúdo normal', () => {
        const wrapper = mountComponent({
            loading: true,
        })

        expect(wrapper.get('button').text()).not.toContain('Salvar')
    })

    it('não emite click durante loading', async () => {
        const wrapper = mountComponent({
            loading: true,
        })

        await wrapper.get('button').trigger('click')

        expect(wrapper.emitted('click')).toBeUndefined()
    })

    it('renderiza ícone no início', () => {
        const wrapper = mountComponent({
            icon: 'save',
            iconPosition: 'start',
        })

        const button = wrapper.get('button')

        expect(button.find('svg').exists()).toBe(true)

        const children = button.element.children

        expect(children[0].tagName.toLowerCase()).toBe('svg')
    })

    it('renderiza ícone no final', () => {
        const wrapper = mountComponent({
            icon: 'save',
            iconPosition: 'end',
        })

        const button = wrapper.get('button')
        const children = button.element.children

        expect(children[children.length - 1].tagName.toLowerCase()).toBe('svg')
    })

    it('aplica aria-label', () => {
        const wrapper = mountComponent({
            ariaLabel: 'Salvar documento',
        })

        expect(wrapper.get('button').attributes('aria-label')).toBe('Salvar documento')
    })

    it('aplica classe icon-only quando não há slot padrão', () => {
        const wrapper = mount(AppButton, {
            props: {
                icon: 'save',
                ariaLabel: 'Salvar',
            },
        })

        expect(wrapper.get('button').classes()).toContain('app-button--icon-only')
    })

    it('não aplica icon-only quando existe texto', () => {
        const wrapper = mountComponent({
            icon: 'save',
        })

        expect(wrapper.get('button').classes()).not.toContain('app-button--icon-only')
    })

    it('ícone é decorativo quando acompanha texto', () => {
        const wrapper = mountComponent({
            icon: 'save',
        })

        const svg = wrapper.get('svg')

        expect(svg.attributes('aria-hidden')).toBe('true')
    })
})
