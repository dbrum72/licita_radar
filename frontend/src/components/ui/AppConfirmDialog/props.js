export const appConfirmDialogProps = {
    error: { type: String, default: '' },
    open: {
        type: Boolean,
        default: false,
    },

    title: {
        type: String,
        default: 'Confirmar ação',
    },

    message: {
        type: String,
        required: true,
    },

    confirmLabel: {
        type: String,
        default: 'Confirmar',
    },

    cancelLabel: {
        type: String,
        default: 'Cancelar',
    },

    loading: {
        type: Boolean,
        default: false,
    },
}
