<template>
    <component :is="as" class="card" :class="cardClasses">
        <div v-if="hasHeader" class="card__header">
            <slot name="header">
                <h2 v-if="title" class="card__title">
                    {{ title }}
                </h2>
            </slot>
        </div>

        <div class="card__body">
            <slot />
        </div>

        <div v-if="$slots.footer" class="card__footer">
            <slot name="footer" />
        </div>
    </component>
</template>

<script setup>
import { computed, useSlots } from 'vue'

import { appCardProps } from './props.js'

const props = defineProps(appCardProps)

const slots = useSlots()

const hasHeader = computed(() => Boolean(props.title || slots.header))

const cardClasses = computed(() => ({
    'card--accent': props.variant === 'accent',
    'card--highlight': props.variant === 'highlight',
}))
</script>
