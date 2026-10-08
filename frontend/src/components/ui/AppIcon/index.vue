<template>
    <component
        :is="iconComponent"
        :size="size"
        :stroke-width="strokeWidth"
        :aria-hidden="decorative"
        :aria-label="decorative ? undefined : label"
    />
</template>

<script setup>
import { computed } from 'vue'

import { icons } from '@/icons/index.js'
import { appIconProps } from './props.js'

const props = defineProps(appIconProps)

const iconComponent = computed(() => icons[props.name] ?? icons.file)
</script>
