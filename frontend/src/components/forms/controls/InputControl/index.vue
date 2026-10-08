<template>
    <input
        class="input-control"
        :value="modelValue"
        :type="type"
        :id="id"
        :name="name"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :autofocus="autofocus"
        :autocomplete="autocomplete"
        :maxlength="maxlength"
        :minlength="minlength"
        :inputmode="inputmode"
        :min="min"
        :max="max"
        :step="step"
        :aria-invalid="ariaInvalid"
        :aria-describedby="ariaDescribedBy"
        @input="emitModelValue"
        @focus="emit('focus', $event)"
        @blur="emit('blur', $event)"
    />
</template>

<script setup>
import { useFieldContext } from '@/composables/useFieldContext.js'
import { inputControlProps } from './props.js'

defineProps(inputControlProps)

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const { ariaDescribedBy, ariaInvalid } = useFieldContext()

function emitModelValue(event) {
    emit('update:modelValue', event.target.value)
}
</script>

<style src="./style.css"></style>
