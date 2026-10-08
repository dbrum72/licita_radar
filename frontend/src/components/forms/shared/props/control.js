import { commonProps } from './common.js'

export const controlProps = {
    ...commonProps,

    name: {
        type: String,
        default: undefined,
    },

    autofocus: {
        type: Boolean,
        default: false,
    },
}
