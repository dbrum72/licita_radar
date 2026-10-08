import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppIcon from '@/components/ui/AppIcon/index.vue'

describe('AppIcon', () => {
    function mountComponent(props = {}) {
        return mount(AppIcon, {
            props: {
                name: 'save',
                ...props,
            },
        })
    }

    it('renderiza um svg', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('svg').exists()).toBe(true)
    })

    it('renderiza o ícone save', () => {
        const wrapper = mountComponent({
            name: 'save',
        })

        expect(wrapper.get('svg').classes()).toContain('lucide-save')
    })

    it('renderiza o ícone arrow-right', () => {
        const wrapper = mountComponent({
            name: 'arrow-right',
        })

        expect(wrapper.get('svg').classes()).toContain('lucide-arrow-right')
    })

    it('usa file como fallback para ícone desconhecido', () => {
        const wrapper = mountComponent({
            name: 'icone-inexistente',
        })

        expect(wrapper.get('svg').classes()).toContain('lucide-file-text')
    })

    it('utiliza tamanho 20 por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('svg').attributes('width')).toBe('20')

        expect(wrapper.get('svg').attributes('height')).toBe('20')
    })

    it('encaminha size', () => {
        const wrapper = mountComponent({
            size: 32,
        })

        expect(wrapper.get('svg').attributes('width')).toBe('32')

        expect(wrapper.get('svg').attributes('height')).toBe('32')
    })

    it('utiliza strokeWidth 2 por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('svg').attributes('stroke-width')).toBe('2')
    })

    it('encaminha strokeWidth', () => {
        const wrapper = mountComponent({
            strokeWidth: 1.5,
        })

        expect(wrapper.get('svg').attributes('stroke-width')).toBe('1.5')
    })

    it('é decorativo por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('svg').attributes('aria-hidden')).toBe('true')

        expect(wrapper.get('svg').attributes('aria-label')).toBeUndefined()
    })

    it('permite ícone não decorativo com label', () => {
        const wrapper = mountComponent({
            decorative: false,
            label: 'Salvar',
        })

        const svg = wrapper.get('svg')

        expect(svg.attributes('aria-hidden')).toBe('false')

        expect(svg.attributes('aria-label')).toBe('Salvar')
    })

    it('remove aria-label quando decorativo', () => {
        const wrapper = mountComponent({
            decorative: true,
            label: 'Salvar',
        })

        expect(wrapper.get('svg').attributes('aria-label')).toBeUndefined()
    })

    it('reage à alteração de name', async () => {
        const wrapper = mountComponent({
            name: 'save',
        })

        expect(wrapper.get('svg').classes()).toContain('lucide-save')

        await wrapper.setProps({
            name: 'arrow-right',
        })

        expect(wrapper.get('svg').classes()).toContain('lucide-arrow-right')
    })
})
