import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppCard from '@/components/ui/AppCard/index.vue'

describe('AppCard', () => {
    function mountComponent(props = {}, slots = {}) {
        return mount(AppCard, {
            props,
            slots: {
                default: '<p>Conteúdo</p>',
                ...slots,
            },
        })
    }

    it('renderiza section por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.element.tagName.toLowerCase()).toBe('section')
    })

    it('permite alterar o elemento raiz', () => {
        const wrapper = mountComponent({
            as: 'article',
        })

        expect(wrapper.element.tagName.toLowerCase()).toBe('article')
    })

    it('aplica a classe base card', () => {
        const wrapper = mountComponent()

        expect(wrapper.classes()).toContain('card')
    })

    it('não aplica variante adicional por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.classes()).not.toContain('card--accent')

        expect(wrapper.classes()).not.toContain('card--highlight')
    })

    it('aplica variante accent', () => {
        const wrapper = mountComponent({
            variant: 'accent',
        })

        expect(wrapper.classes()).toContain('card--accent')
    })

    it('aplica variante highlight', () => {
        const wrapper = mountComponent({
            variant: 'highlight',
        })

        expect(wrapper.classes()).toContain('card--highlight')
    })

    it('renderiza o body sempre', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('.card__body').exists()).toBe(true)
    })

    it('renderiza o slot padrão dentro do body', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('.card__body').text()).toBe('Conteúdo')
    })

    it('não renderiza header sem title nem slot header', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('.card__header').exists()).toBe(false)
    })

    it('renderiza header quando title está definido', () => {
        const wrapper = mountComponent({
            title: 'Processo',
        })

        expect(wrapper.find('.card__header').exists()).toBe(true)

        expect(wrapper.get('.card__title').text()).toBe('Processo')
    })

    it('renderiza o título como h2', () => {
        const wrapper = mountComponent({
            title: 'Processo',
        })

        expect(wrapper.get('.card__title').element.tagName.toLowerCase()).toBe('h2')
    })

    it('renderiza slot header', () => {
        const wrapper = mountComponent(
            {
                title: 'Título padrão',
            },
            {
                header: '<div class="custom-header">Custom</div>',
            },
        )

        expect(wrapper.get('.custom-header').text()).toBe('Custom')
    })

    it('slot header substitui o title padrão', () => {
        const wrapper = mountComponent(
            {
                title: 'Título padrão',
            },
            {
                header: '<strong>Custom</strong>',
            },
        )

        expect(wrapper.find('.card__title').exists()).toBe(false)

        expect(wrapper.get('.card__header').text()).toBe('Custom')
    })

    it('não renderiza footer quando slot está ausente', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('.card__footer').exists()).toBe(false)
    })

    it('renderiza footer quando slot está presente', () => {
        const wrapper = mountComponent(
            {},
            {
                footer: '<button type="button">Salvar</button>',
            },
        )

        expect(wrapper.find('.card__footer').exists()).toBe(true)

        expect(wrapper.get('.card__footer').text()).toBe('Salvar')
    })

    it('mantém header, body e footer na ordem correta', () => {
        const wrapper = mountComponent(
            {
                title: 'Título',
            },
            {
                footer: '<span>Rodapé</span>',
            },
        )

        const children = Array.from(wrapper.element.children)

        expect(children).toHaveLength(3)

        expect(children[0].classList).toContain('card__header')

        expect(children[1].classList).toContain('card__body')

        expect(children[2].classList).toContain('card__footer')
    })
})
