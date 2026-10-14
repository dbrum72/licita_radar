import { describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import AppTable from '@/components/ui/AppTable/index.vue'

const columns = [
    {
        key: 'name',
        label: 'Nome',
    },
    {
        key: 'email',
        label: 'E-mail',
    },
    {
        key: 'actions',
        label: 'Ações',
        align: 'end',
    },
]

const rows = [
    {
        id: 1,
        name: 'Maria da Silva',
        email: 'maria@example.com',
    },
    {
        id: 2,
        name: 'João Souza',
        email: null,
    },
]

function mountComponent(props = {}, slots = {}) {
    return mount(AppTable, {
        props: {
            columns,
            rows,
            ...props,
        },

        slots,
    })
}

describe('AppTable', () => {
    it('renderiza elemento table', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('table').exists()).toBe(true)
    })

    it('renderiza cabeçalhos', () => {
        const wrapper = mountComponent()

        const headers = wrapper.findAll('th')

        expect(headers).toHaveLength(3)

        expect(headers[0].text()).toBe('Nome')

        expect(headers[1].text()).toBe('E-mail')

        expect(headers[2].text()).toBe('Ações')
    })

    it('renderiza linhas recebidas', () => {
        const wrapper = mountComponent()

        const bodyRows = wrapper.find('tbody').findAll('tr')

        expect(bodyRows).toHaveLength(2)
    })

    it('renderiza valores das células', () => {
        const wrapper = mountComponent()

        expect(wrapper.text()).toContain('Maria da Silva')

        expect(wrapper.text()).toContain('maria@example.com')

        expect(wrapper.text()).toContain('João Souza')
    })

    it('renderiza fallback para valor nulo', () => {
        const wrapper = mountComponent()

        const bodyRows = wrapper.find('tbody').findAll('tr')

        expect(bodyRows[1].text()).toContain('—')
    })

    it('renderiza estado vazio', () => {
        const wrapper = mountComponent({
            rows: [],
        })

        expect(wrapper.text()).toContain('Nenhum registro encontrado.')
    })

    it('aceita texto personalizado para estado vazio', () => {
        const wrapper = mountComponent({
            rows: [],
            emptyText: 'Nenhum cliente cadastrado.',
        })

        expect(wrapper.text()).toContain('Nenhum cliente cadastrado.')
    })

    it('estado vazio ocupa todas as colunas', () => {
        const wrapper = mountComponent({
            rows: [],
        })

        expect(wrapper.find('.app-table__empty').attributes('colspan')).toBe('3')
    })

    it('renderiza caption quando informado', () => {
        const wrapper = mountComponent({
            caption: 'Lista de clientes',
        })

        const caption = wrapper.find('caption')

        expect(caption.exists()).toBe(true)

        expect(caption.text()).toBe('Lista de clientes')
    })

    it('não renderiza caption quando não informado', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('caption').exists()).toBe(false)
    })

    it('aplica alinhamento padrão start', () => {
        const wrapper = mountComponent()

        const headers = wrapper.findAll('th')

        expect(headers[0].classes()).toContain('app-table--align-start')
    })

    it('aplica alinhamento configurado na coluna', () => {
        const wrapper = mountComponent()

        const headers = wrapper.findAll('th')

        expect(headers[2].classes()).toContain('app-table--align-end')
    })

    it('aplica alinhamento também às células', () => {
        const wrapper = mountComponent()

        const firstRow = wrapper.find('tbody').find('tr')

        const cells = firstRow.findAll('td')

        expect(cells[2].classes()).toContain('app-table--align-end')
    })

    it('permite customizar célula por slot', () => {
        const wrapper = mountComponent(
            {},
            {
                'cell-name': `
          <template #cell-name="{ value }">
            <strong class="custom-name">
              {{ value }}
            </strong>
          </template>
        `,
            },
        )

        const custom = wrapper.find('.custom-name')

        expect(custom.exists()).toBe(true)

        expect(custom.text()).toBe('Maria da Silva')
    })

    it('slot de célula recebe row e value', () => {
        const wrapper = mountComponent(
            {},
            {
                'cell-email': `
          <template #cell-email="{ row, value }">
            <span class="custom-email">
              {{ row.id }}:{{ value }}
            </span>
          </template>
        `,
            },
        )

        const custom = wrapper.findAll('.custom-email')

        expect(custom[0].text()).toBe('1:maria@example.com')
    })

    it('permite customizar estado vazio por slot', () => {
        const wrapper = mountComponent(
            {
                rows: [],
            },
            {
                empty: `
          <div class="custom-empty">
            Cadastre o primeiro cliente.
          </div>
        `,
            },
        )

        expect(wrapper.find('.custom-empty').exists()).toBe(true)

        expect(wrapper.text()).toContain('Cadastre o primeiro cliente.')
    })

    it('respeita rowKey personalizado', () => {
        const wrapper = mountComponent({
            rowKey: 'code',

            rows: [
                {
                    code: 'CLI-001',
                    name: 'Cliente A',
                    email: 'a@example.com',
                },
                {
                    code: 'CLI-002',
                    name: 'Cliente B',
                    email: 'b@example.com',
                },
            ],
        })

        expect(wrapper.find('tbody').findAll('tr')).toHaveLength(2)
    })
})
