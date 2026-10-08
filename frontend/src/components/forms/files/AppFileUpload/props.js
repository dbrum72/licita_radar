import { fieldProps } from '@/components/forms/shared/props/field.js'

const { readonly, ...fileUploadFieldProps } = fieldProps

export const appFileUploadProps = {
    ...fileUploadFieldProps,

    modelValue: {
        type: Array,
        default: () => [],
    },

    name: {
        type: String,
        default: undefined,
    },

    accept: {
        type: String,
        default: '',
    },

    multiple: {
        type: Boolean,
        default: false,
    },

    maxFiles: {
        type: Number,
        default: undefined,
        validator: (value) => Number.isInteger(value) && value > 0,
    },

    maxFileSize: {
        type: Number,
        default: undefined,
        validator: (value) => Number.isFinite(value) && value > 0,
    },

    browseLabel: {
        type: String,
        default: 'Selecionar arquivo',
    },

    removeLabel: {
        type: String,
        default: 'Remover arquivo',
    },

    emptyText: {
        type: String,
        default: 'Nenhum arquivo selecionado.',
    },
}
