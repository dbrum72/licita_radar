<template>
    <select
        class="select-control"
        :value="modelValue"
        :id="id"
        :name="name"
        :disabled="disabled"
        :required="required"
        :autofocus="autofocus"
        :multiple="multiple"
        :aria-invalid="ariaInvalid"
        :aria-describedby="ariaDescribedBy"
        @change="emitModelValue"
        @focus="emit('focus', $event)"
        @blur="emit('blur', $event)"
    >
        <option v-if="placeholder" value="" disabled>
            {{ placeholder }}
        </option>

        <option
            v-for="option in options"
            :key="getOptionKey(option)"
            :value="getOptionValue(option)"
            :selected="multiple ? (modelValue || []).includes(getOptionValue(option)) : undefined"
        >
            {{ getOptionLabel(option) }}
        </option>
    </select>
</template>

<script setup>
import { useFieldContext } from '@/composables/useFieldContext.js'
import { selectControlProps } from './props.js'

const props = defineProps(selectControlProps)

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const { ariaDescribedBy, ariaInvalid } = useFieldContext()

function getOptionLabel(option) {
    if (option !== null && typeof option === 'object') {
        return option[props.optionLabel]
    }

    return option
}

function getOptionValue(option) {
    if (option !== null && typeof option === 'object') {
        return option[props.optionValue]
    }

    return option
}

function getOptionKey(option) {
    return getOptionValue(option)
}

function emitModelValue(event) {
    if (props.multiple) {
        const offset = props.placeholder ? 1 : 0
        const selected = [...event.target.options].flatMap((option, index) =>
            option.selected && index >= offset
                ? [getOptionValue(props.options[index - offset])]
                : [],
        )
        emit('update:modelValue', selected)
        return
    }
    const selectedIndex = event.target.selectedIndex

    if (selectedIndex < 0) {
        emit('update:modelValue', null)
        return
    }

    const optionIndex = props.placeholder ? selectedIndex - 1 : selectedIndex

    if (optionIndex < 0 || optionIndex >= props.options.length) {
        emit('update:modelValue', null)
        return
    }

    const selectedOption = props.options[optionIndex]

    emit('update:modelValue', getOptionValue(selectedOption))
}
</script>

<style src="./style.css"></style>
