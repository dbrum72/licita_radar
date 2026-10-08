<template>
    <AppField
        :id="id"
        :label="label"
        :hint="hint"
        :error="error"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
    >
        <template v-if="$slots.prepend" #prepend>
            <slot name="prepend" />
        </template>

        <slot />

        <template v-if="$slots.append" #append>
            <slot name="append" />
        </template>
    </AppField>
</template>

<script setup>
import AppField from '@/components/forms/fields/AppField/index.vue'
import { fieldProps } from '@/components/forms/shared/props/field.js'

defineProps(fieldProps)
</script>
