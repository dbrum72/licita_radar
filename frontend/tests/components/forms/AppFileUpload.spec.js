import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppFileUpload from '@/components/forms/files/AppFileUpload/index.vue'

describe('AppFileUpload', () => {
    function createFile(name = 'documento.pdf', size = 1024, type = 'application/pdf') {
        const content = new Uint8Array(size)

        return new File([content], name, {
            type,
            lastModified: 1,
        })
    }

    function mountComponent(props = {}) {
        return mount(AppFileUpload, {
            props: {
                modelValue: [],
                id: 'attachments',
                name: 'attachments',
                label: 'Anexos',
                ...props,
            },
        })
    }

    async function selectFiles(wrapper, files) {
        const input = wrapper.get('input[type="file"]')

        Object.defineProperty(input.element, 'files', {
            configurable: true,
            value: files,
        })

        await input.trigger('change')
    }

    it('renderiza input do tipo file', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('type')).toBe('file')
        expect(input.attributes('id')).toBe('attachments-input')
        expect(input.attributes('name')).toBe('attachments')
    })

    it('renderiza label associada ao input', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('label').attributes('for')).toBe('attachments-input')

        expect(wrapper.get('label').text()).toContain('Anexos')
    })

    it('renderiza texto de estado vazio', () => {
        const wrapper = mountComponent()

        expect(wrapper.text()).toContain('Nenhum arquivo selecionado.')
    })

    it('permite personalizar texto de estado vazio', () => {
        const wrapper = mountComponent({
            emptyText: 'Nenhum anexo.',
        })

        expect(wrapper.text()).toContain('Nenhum anexo.')
    })

    it('encaminha accept', () => {
        const wrapper = mountComponent({
            accept: '.pdf,image/*',
        })

        expect(wrapper.get('input').attributes('accept')).toBe('.pdf,image/*')
    })

    it('não renderiza accept quando vazio', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('input').attributes('accept')).toBeUndefined()
    })

    it('encaminha multiple', () => {
        const wrapper = mountComponent({
            multiple: true,
        })

        expect(wrapper.get('input').attributes()).toHaveProperty('multiple')
    })

    it('seleciona um único arquivo', async () => {
        const wrapper = mountComponent()

        const file = createFile()

        await selectFiles(wrapper, [file])

        expect(wrapper.emitted('update:modelValue')).toEqual([[[file]]])

        expect(wrapper.emitted('select')).toEqual([[[file]]])
    })

    it('substitui a seleção quando multiple é false', async () => {
        const oldFile = createFile('antigo.pdf')
        const newFile = createFile('novo.pdf')

        const wrapper = mountComponent({
            modelValue: [oldFile],
        })

        await selectFiles(wrapper, [newFile])

        expect(wrapper.emitted('update:modelValue')).toEqual([[[newFile]]])
    })

    it('rejeita arquivos adicionais quando multiple é false', async () => {
        const first = createFile('primeiro.pdf')
        const second = createFile('segundo.pdf')

        const wrapper = mountComponent()

        await selectFiles(wrapper, [first, second])

        expect(wrapper.emitted('update:modelValue')).toEqual([[[first]]])

        expect(wrapper.emitted('reject')).toEqual([
            [
                {
                    file: second,
                    reason: 'max-files',
                },
            ],
        ])
    })

    it('acrescenta arquivos quando multiple é true', async () => {
        const existing = createFile('existente.pdf')
        const first = createFile('primeiro.pdf')
        const second = createFile('segundo.pdf')

        const wrapper = mountComponent({
            modelValue: [existing],
            multiple: true,
        })

        await selectFiles(wrapper, [first, second])

        expect(wrapper.emitted('update:modelValue')).toEqual([[[existing, first, second]]])

        expect(wrapper.emitted('select')).toEqual([[[first, second]]])
    })

    it('não altera o array original ao adicionar arquivos', async () => {
        const existing = createFile('existente.pdf')
        const newFile = createFile('novo.pdf')

        const modelValue = [existing]

        const wrapper = mountComponent({
            modelValue,
            multiple: true,
        })

        await selectFiles(wrapper, [newFile])

        expect(modelValue).toEqual([existing])
    })

    it('respeita maxFiles', async () => {
        const existing = createFile('existente.pdf')
        const first = createFile('primeiro.pdf')
        const second = createFile('segundo.pdf')

        const wrapper = mountComponent({
            modelValue: [existing],
            multiple: true,
            maxFiles: 2,
        })

        await selectFiles(wrapper, [first, second])

        expect(wrapper.emitted('update:modelValue')).toEqual([[[existing, first]]])

        expect(wrapper.emitted('reject')).toEqual([
            [
                {
                    file: second,
                    reason: 'max-files',
                },
            ],
        ])
    })

    it('rejeita todos quando maxFiles já foi atingido', async () => {
        const existing = createFile('existente.pdf')
        const newFile = createFile('novo.pdf')

        const wrapper = mountComponent({
            modelValue: [existing],
            multiple: true,
            maxFiles: 1,
        })

        await selectFiles(wrapper, [newFile])

        expect(wrapper.emitted('update:modelValue')).toBeUndefined()

        expect(wrapper.emitted('select')).toBeUndefined()

        expect(wrapper.emitted('reject')).toEqual([
            [
                {
                    file: newFile,
                    reason: 'max-files',
                },
            ],
        ])
    })

    it('rejeita arquivo acima de maxFileSize', async () => {
        const file = createFile('grande.pdf', 2048)

        const wrapper = mountComponent({
            maxFileSize: 1024,
        })

        await selectFiles(wrapper, [file])

        expect(wrapper.emitted('update:modelValue')).toBeUndefined()

        expect(wrapper.emitted('select')).toBeUndefined()

        expect(wrapper.emitted('reject')).toEqual([
            [
                {
                    file,
                    reason: 'max-file-size',
                },
            ],
        ])
    })

    it('aceita arquivos dentro do limite de tamanho', async () => {
        const file = createFile('arquivo.pdf', 1024)

        const wrapper = mountComponent({
            maxFileSize: 1024,
        })

        await selectFiles(wrapper, [file])

        expect(wrapper.emitted('update:modelValue')).toEqual([[[file]]])
    })

    it('mantém arquivos válidos e rejeita inválidos individualmente', async () => {
        const valid = createFile('valido.pdf', 500)

        const invalid = createFile('grande.pdf', 2000)

        const wrapper = mountComponent({
            multiple: true,
            maxFileSize: 1000,
        })

        await selectFiles(wrapper, [invalid, valid])

        expect(wrapper.emitted('update:modelValue')).toEqual([[[valid]]])

        expect(wrapper.emitted('select')).toEqual([[[valid]]])

        expect(wrapper.emitted('reject')).toEqual([
            [
                {
                    file: invalid,
                    reason: 'max-file-size',
                },
            ],
        ])
    })

    it('renderiza arquivos presentes no modelValue', () => {
        const file = createFile('contrato.pdf', 2048)

        const wrapper = mountComponent({
            modelValue: [file],
        })

        expect(wrapper.text()).toContain('contrato.pdf')

        expect(wrapper.text()).toContain('2.0 KB')
    })

    it('remove arquivo', async () => {
        const first = createFile('primeiro.pdf')
        const second = createFile('segundo.pdf')

        const wrapper = mountComponent({
            modelValue: [first, second],
            multiple: true,
        })

        const buttons = wrapper.findAll('.app-file-upload__remove')

        await buttons[0].trigger('click')

        expect(wrapper.emitted('update:modelValue')).toEqual([[[second]]])

        expect(wrapper.emitted('remove')).toEqual([[first]])
    })

    it('não altera o array original ao remover', async () => {
        const first = createFile('primeiro.pdf')
        const second = createFile('segundo.pdf')

        const modelValue = [first, second]

        const wrapper = mountComponent({
            modelValue,
            multiple: true,
        })

        await wrapper.findAll('.app-file-upload__remove')[0].trigger('click')

        expect(modelValue).toEqual([first, second])
    })

    it('não remove arquivo quando disabled', async () => {
        const file = createFile()

        const wrapper = mountComponent({
            modelValue: [file],
            disabled: true,
        })

        await wrapper.get('.app-file-upload__remove').trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeUndefined()

        expect(wrapper.emitted('remove')).toBeUndefined()
    })

    it('encaminha disabled ao input e aos botões', () => {
        const file = createFile()

        const wrapper = mountComponent({
            modelValue: [file],
            disabled: true,
        })

        expect(wrapper.get('input').attributes()).toHaveProperty('disabled')

        expect(wrapper.get('.app-file-upload__browse').attributes()).toHaveProperty('disabled')

        expect(wrapper.get('.app-file-upload__remove').attributes()).toHaveProperty('disabled')
    })

    it('aplica required ao input quando não há arquivos', () => {
        const wrapper = mountComponent({
            required: true,
        })

        expect(wrapper.get('input').attributes()).toHaveProperty('required')
    })

    it('remove required nativo quando já existe arquivo', () => {
        const wrapper = mountComponent({
            required: true,
            modelValue: [createFile()],
        })

        expect(wrapper.get('input').attributes('required')).toBeUndefined()
    })

    it('renderiza indicador obrigatório', () => {
        const wrapper = mountComponent({
            required: true,
        })

        expect(wrapper.get('.app-file-upload__required').text()).toBe('*')
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Arquivos PDF de até 5 MB.',
        })

        expect(wrapper.text()).toContain('Arquivos PDF de até 5 MB.')

        expect(wrapper.get('.app-file-upload__hint').attributes('id')).toBe('attachments-hint')
    })

    it('renderiza erro e oculta hint', () => {
        const wrapper = mountComponent({
            hint: 'Arquivos PDF de até 5 MB.',
            error: 'Arquivo inválido.',
        })

        expect(wrapper.text()).toContain('Arquivo inválido.')

        expect(wrapper.text()).not.toContain('Arquivos PDF de até 5 MB.')
    })

    it('associa hint por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Arquivos PDF de até 5 MB.',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('attachments-hint')
    })

    it('associa erro e marca input como inválido', () => {
        const wrapper = mountComponent({
            error: 'Arquivo inválido.',
        })

        const input = wrapper.get('input')

        expect(input.attributes('aria-describedby')).toBe('attachments-error')

        expect(input.attributes('aria-invalid')).toBe('true')
    })

    it('emite focus e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('formata bytes', () => {
        const wrapper = mountComponent({
            modelValue: [createFile('pequeno.txt', 512)],
        })

        expect(wrapper.text()).toContain('512 B')
    })

    it('formata megabytes', () => {
        const wrapper = mountComponent({
            modelValue: [createFile('grande.pdf', 2 * 1024 * 1024)],
        })

        expect(wrapper.text()).toContain('2.0 MB')
    })
})
