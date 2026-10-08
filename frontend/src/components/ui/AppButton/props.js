export const appButtonProps = {
    type: {
        type: String,
        default: 'button',
        validator: (value) => ['button', 'submit', 'reset'].includes(value),
    },

    variant: {
        type: String,
        default: 'primary',
        validator: (value) =>
            [
                'primary',
                'accent',
                'highlight',
                'outline',
                'ghost',
                'navigation',
                'danger',
                'action',
                'route',
                'modal',
                'filter',
            ].includes(value),
    },

    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    loading: {
        type: Boolean,
        default: false,
    },

    block: {
        type: Boolean,
        default: false,
    },

    icon: {
        type: String,
        default: undefined,
    },

    iconPosition: {
        type: String,
        default: 'start',
        validator: (value) => ['start', 'end'].includes(value),
    },

    ariaLabel: {
        type: String,
        default: undefined,
    },
}
