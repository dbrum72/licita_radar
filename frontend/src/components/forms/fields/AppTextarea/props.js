import { fieldProps } from '@/components/forms/shared/props/field.js'
import { controlProps } from '@/components/forms/shared/props/control.js'

export const appTextareaProps = {
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

    rows: {
        type: Number,
        default: 4,
    },

    cols: {
        type: Number,
        default: undefined,
    },

    wrap: {
        type: String,
        default: 'soft',
        validator: (value) => ['soft', 'hard', 'off'].includes(value),
    },
}
