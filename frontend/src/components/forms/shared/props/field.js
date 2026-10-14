import { commonProps } from './common.js'

export const fieldProps = {
    ...commonProps,

    readonly: {
        type: Boolean,
        default: false,
    },

    label: {
        type: String,
        default: '',
    },

    hint: {
        type: String,
        default: '',
    },

    error: {
        type: String,
        default: '',
    },
}
