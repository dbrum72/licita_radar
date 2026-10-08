export const textareaControlProps = {
    modelValue: {
        type: [String, Number],
        default: '',
    },

    id: {
        type: String,
        default: undefined,
    },

    name: {
        type: String,
        default: undefined,
    },

    placeholder: {
        type: String,
        default: '',
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    readonly: {
        type: Boolean,
        default: false,
    },

    required: {
        type: Boolean,
        default: false,
    },

    autofocus: {
        type: Boolean,
        default: false,
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
