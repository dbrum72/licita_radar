<template>
    <button
        :type="type"
        class="btn"
        :class="buttonClasses"
        :disabled="isDisabled"
        :aria-busy="loading || undefined"
        :aria-label="ariaLabel"
        @click="handleClick"
    >
        <span v-if="loading" class="app-button__spinner" aria-hidden="true" />

        <template v-else>
            <AppIcon
                v-if="icon && iconPosition === 'start'"
                :name="icon"
                :size="iconSize"
                decorative
            />

            <span v-if="$slots.default" class="app-button__label">
                <slot />
            </span>

            <AppIcon
                v-if="icon && iconPosition === 'end'"
                :name="icon"
                :size="iconSize"
                decorative
            />
        </template>
    </button>
</template>

<script setup>
import { computed, useSlots } from 'vue'

import AppIcon from '@/components/ui/AppIcon/index.vue'
import { appButtonProps } from './props.js'

const props = defineProps(appButtonProps)
const slots = useSlots()

const emit = defineEmits(['click'])

const isDisabled = computed(() => props.disabled || props.loading)

const buttonClasses = computed(() => [
    `btn--${props.variant}`,

    props.size !== 'md' ? `btn--${props.size}` : undefined,

    {
        'app-button--block': props.block,
        'app-button--loading': props.loading,
        'app-button--icon-only': Boolean(props.icon) && !hasDefaultSlot.value,
    },
])

const hasDefaultSlot = computed(() => Boolean(slots.default))

const iconSize = computed(() => {
    if (props.size === 'sm') {
        return 16
    }

    if (props.size === 'lg') {
        return 20
    }

    return 18
})

function handleClick(event) {
    if (isDisabled.value) {
        return
    }

    emit('click', event)
}
</script>

<style src="./style.css"></style>
