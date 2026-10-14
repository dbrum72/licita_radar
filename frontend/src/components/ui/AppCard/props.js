export const appCardProps = {
    title: {
        type: String,
        default: '',
    },

    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'accent', 'highlight'].includes(value),
    },

    as: {
        type: String,
        default: 'section',
    },
}
