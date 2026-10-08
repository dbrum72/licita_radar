<template>
    <div
        class="app-checkbox"
        :class="{
            'app-checkbox--checked': modelValue,
            'app-checkbox--invalid': hasError,
            'app-checkbox--disabled': disabled,
            'app-checkbox--indeterminate': indeterminate,
        }"
    >
        <label class="app-checkbox__label" :for="id">
            <CheckboxControl
                v-bind="controlProps"
                :aria-invalid="hasError || undefined"
                :aria-describedby="ariaDescribedBy"
                @update:model-value="emit('update:modelValue', $event)"
                @focus="emit('focus', $event)"
                @blur="emit('blur', $event)"
            />

            <span class="app-checkbox__content">
                <span class="app-checkbox__text">
                    {{ label }}

                    <span v-if="required" class="app-checkbox__required" aria-hidden="true">
                        *
                    </span>
                </span>

                <span v-if="hint && !hasError" :id="hintId" class="app-checkbox__hint">
                    {{ hint }}
                </span>

                <span v-if="hasError" :id="errorId" class="app-checkbox__error">
                    {{ error }}
                </span>
            </span>
        </label>
    </div>
</template>

<script setup>
import { computed } from 'vue'

import CheckboxControl from '@/components/forms/controls/CheckboxControl/index.vue'
import { appCheckboxProps } from './props.js'
import { pick } from '@/components/forms/shared/utils/pick.js'
import { CHECKBOX_CONTROL_KEYS } from '@/components/forms/shared/constants/control-keys.js'

const props = defineProps(appCheckboxProps)

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const hasError = computed(() => Boolean(props.error))

const hintId = computed(() => (props.id && props.hint ? `${props.id}-hint` : undefined))

const errorId = computed(() => (props.id ? `${props.id}-error` : undefined))

const ariaDescribedBy = computed(() => (hasError.value ? errorId.value : hintId.value))

const controlProps = computed(() => pick(props, CHECKBOX_CONTROL_KEYS))
</script>

<style src="./style.css"></style>
