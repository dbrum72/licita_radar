<template>
    <BaseField
        :id="id"
        :label="label"
        :hint="hint"
        :error="error"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
    >
        <SelectControl
            v-bind="controlProps"
            @update:model-value="emit('update:modelValue', $event)"
            @focus="emit('focus', $event)"
            @blur="emit('blur', $event)"
        />
    </BaseField>
</template>

<script setup>
import { computed } from 'vue'

import SelectControl from '@/components/forms/controls/SelectControl/index.vue'
import BaseField from '@/components/forms/fields/BaseField/index.vue'
import { SELECT_CONTROL_KEYS } from '@/components/forms/shared/constants/control-keys.js'
import { pick } from '@/components/forms/shared/utils/pick.js'
import { appSelectProps } from './props.js'

const props = defineProps(appSelectProps)

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const controlProps = computed(() => pick(props, SELECT_CONTROL_KEYS))
</script>
