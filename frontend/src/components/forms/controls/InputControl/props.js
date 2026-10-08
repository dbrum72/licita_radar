export const inputControlProps = {
    modelValue: {
        type: [String, Number],
        default: '',
    },

    type: {
        type: String,
        default: 'text',
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
}
