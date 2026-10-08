<template>
    <div
        class="app-field"
        :class="{
            'app-field--required': required,
            'app-field--invalid': hasError,
            'app-field--disabled': disabled,
            'app-field--readonly': readonly,
        }"
    >
        <label v-if="label" class="app-field__label" :for="id">
            {{ label }}

            <span v-if="required" class="app-field__required" aria-hidden="true"> * </span>
        </label>

        <div class="app-field__control">
            <div v-if="$slots.prepend" class="app-field__prepend">
                <slot name="prepend" />
            </div>

            <div class="app-field__input">
                <slot />
            </div>

            <div v-if="$slots.append" class="app-field__append">
                <slot name="append" />
            </div>
        </div>

        <p v-if="hint && !hasError" :id="hintId" class="app-field__hint">
            {{ hint }}
        </p>

        <p v-if="hasError" :id="errorId" class="app-field__error">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed, provide } from 'vue'

import { FIELD_CONTEXT } from '@/composables/field-context.js'
import { appFieldProps } from './props.js'

const props = defineProps(appFieldProps)

const hasError = computed(() => Boolean(props.error))

const hintId = computed(() => (props.id && props.hint ? `${props.id}-hint` : undefined))

const errorId = computed(() => (props.id ? `${props.id}-error` : undefined))

const fieldContext = computed(() => ({
    id: props.id,
    hintId: hintId.value,
    errorId: errorId.value,
    required: props.required,
    disabled: props.disabled,
    readonly: props.readonly,
    invalid: hasError.value,
}))

provide(FIELD_CONTEXT, fieldContext)
</script>
