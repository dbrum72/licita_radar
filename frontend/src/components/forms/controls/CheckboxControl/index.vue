<template>
    <input
        ref="inputRef"
        class="checkbox-control"
        type="checkbox"
        :id="id"
        :name="name"
        :checked="modelValue"
        :disabled="disabled"
        :required="required"
        :autofocus="autofocus"
        :aria-invalid="ariaInvalid"
        :aria-describedby="ariaDescribedBy"
        @change="emitModelValue"
        @focus="emit('focus', $event)"
        @blur="emit('blur', $event)"
    />
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'

import { checkboxControlProps } from './props.js'

const props = defineProps(checkboxControlProps)

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const inputRef = ref(null)

watch(() => props.indeterminate, applyIndeterminate)

onMounted(applyIndeterminate)

function applyIndeterminate() {
    if (!inputRef.value) {
        return
    }

    inputRef.value.indeterminate = props.indeterminate
}

function emitModelValue(event) {
    emit('update:modelValue', event.target.checked)
}
</script>

<style src="./style.css"></style>
