<template>
    <textarea
        class="textarea-control"
        :value="modelValue"
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
        :rows="rows"
        :cols="cols"
        :wrap="wrap"
        :aria-invalid="ariaInvalid"
        :aria-describedby="ariaDescribedBy"
        @input="emitModelValue"
        @focus="emit('focus', $event)"
        @blur="emit('blur', $event)"
    />
</template>

<script setup>
import { useFieldContext } from '@/composables/useFieldContext.js'
import { textareaControlProps } from './props.js'

defineProps(textareaControlProps)

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const { ariaDescribedBy, ariaInvalid } = useFieldContext()

function emitModelValue(event) {
    emit('update:modelValue', event.target.value)
}
</script>

<style src="./style.css"></style>
