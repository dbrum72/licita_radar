import { computed, inject } from 'vue'

import { FIELD_CONTEXT } from '@/composables/field-context.js'

export function useFieldContext() {
    const fieldContext = inject(FIELD_CONTEXT, null)

    const ariaDescribedBy = computed(() => {
        const field = fieldContext?.value

        if (!field) {
            return undefined
        }

        return field.invalid ? field.errorId : field.hintId
    })

    const ariaInvalid = computed(() => {
        return fieldContext?.value?.invalid ? 'true' : undefined
    })

    return {
        fieldContext,
        ariaDescribedBy,
        ariaInvalid,
    }
}
