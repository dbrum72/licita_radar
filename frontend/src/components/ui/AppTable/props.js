export const appTableProps = {
    columns: {
        type: Array,
        required: true,
    },

    rows: {
        type: Array,
        default: () => [],
    },

    rowKey: {
        type: String,
        default: 'id',
    },

    emptyText: {
        type: String,
        default: 'Nenhum registro encontrado.',
    },

    caption: {
        type: String,
        default: '',
    },
}
