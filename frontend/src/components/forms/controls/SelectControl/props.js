export const selectControlProps = {
    multiple: { type: Boolean, default: false },
    modelValue: {
        type: [String, Number, Boolean, Object, Array, null],
        default: null,
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

    required: {
        type: Boolean,
        default: false,
    },

    autofocus: {
        type: Boolean,
        default: false,
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
