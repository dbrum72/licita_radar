import { fieldProps } from '@/components/forms/shared/props/field.js'
import { controlProps } from '@/components/forms/shared/props/control.js'

export const appInputProps = {
    modelModifiers: { type: Object, default: () => ({}) },
    ...fieldProps,
    ...controlProps,

    modelValue: {
        type: [String, Number],
        default: '',
    },

    placeholder: {
        type: String,
        default: '',
    },

    autocomplete: {
        type: String,
        default: 'off',
    },

    maxlength: {
        type: Number,
        default: undefined,
    },

    minlength: {
        type: Number,
        default: undefined,
    },

    inputmode: {
        type: String,
        default: undefined,
    },

    min: {
        type: [Number, String],
        default: undefined,
    },

    max: {
        type: [Number, String],
        default: undefined,
    },

    step: {
        type: [Number, String],
        default: undefined,
    },

    type: {
        type: String,
        default: 'text',
    },
}
