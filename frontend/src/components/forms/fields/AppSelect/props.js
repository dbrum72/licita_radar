import { fieldProps } from '@/components/forms/shared/props/field.js'
import { controlProps } from '@/components/forms/shared/props/control.js'

export const appSelectProps = {
    multiple: { type: Boolean, default: false },
    ...fieldProps,
    ...controlProps,

    modelValue: {
        type: [String, Number, Boolean, Object, Array, null],
        default: null,
    },

    placeholder: {
        type: String,
        default: '',
    },

    options: {
        type: Array,
        default: () => [],
    },

    optionLabel: {
        type: String,
        default: 'label',
    },

    optionValue: {
        type: String,
        default: 'value',
    },
}
