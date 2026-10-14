export const appIconProps = {
    name: {
        type: String,
        required: true,
    },

    size: {
        type: [Number, String],
        default: 20,
    },

    strokeWidth: {
        type: [Number, String],
        default: 2,
    },

    decorative: {
        type: Boolean,
        default: true,
    },

    label: {
        type: String,
        default: undefined,
    },
}
