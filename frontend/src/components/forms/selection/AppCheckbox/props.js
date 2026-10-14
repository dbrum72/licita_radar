import { fieldProps } from '@/components/forms/shared/props/field.js'
import { checkboxControlProps } from '@/components/forms/controls/CheckboxControl/props.js'

export const appCheckboxProps = {
    ...fieldProps,
    ...checkboxControlProps,

    modelValue: {
        type: Boolean,
        default: false,
    },
}
