// @vitest-environment jsdom
import { afterEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import AppDialog from '@/components/ui/AppDialog/index.vue'

const wrappers = []
afterEach(() => {
    wrappers.forEach((wrapper) => wrapper.unmount())
    wrappers.length = 0
    document.body.innerHTML = ''
})
function dialog(props = {}) {
    const wrapper = mount(AppDialog, {
        attachTo: document.body,
        props: { title: 'Teste', open: true, ...props },
        slots: { default: '<input autofocus><button id="last">Salvar</button>' },
    })
    wrappers.push(wrapper)
    return wrapper
}

describe('accessible dialog', () => {
    it('focuses the form, traps Tab and restores focus after closing', async () => {
        const trigger = document.createElement('button')
        document.body.appendChild(trigger)
        trigger.focus()
        const wrapper = dialog()
        await nextTick()
        expect(document.activeElement.tagName).toBe('INPUT')
        const last = wrapper.find('#last').element
        last.focus()
        document.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true }),
        )
        expect(document.activeElement).toBe(wrapper.find('[aria-label="Fechar"]').element)
        document.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }),
        )
        expect(wrapper.emitted('close')).toHaveLength(1)
        await wrapper.setProps({ open: false })
        await nextTick()
        expect(document.activeElement).toBe(trigger)
    })

    it('prevents Escape and backdrop dismissal during a request', async () => {
        const wrapper = dialog({ busy: true })
        await nextTick()
        document.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }),
        )
        await wrapper.find('.modal-overlay').trigger('click')
        expect(wrapper.emitted('close')).toBeUndefined()
        expect(wrapper.find('[aria-label="Fechar"]').attributes('disabled')).toBeDefined()
    })

    it('closes only the topmost dialog', async () => {
        const first = dialog(),
            second = dialog()
        await nextTick()
        document.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }),
        )
        expect(first.emitted('close')).toBeUndefined()
        expect(second.emitted('close')).toHaveLength(1)
    })
})
