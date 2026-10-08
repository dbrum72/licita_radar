<script setup>
import { AppButton } from '@/components/ui'
defineProps({
    items: { type: Array, required: true },
    activeId: String,
    opportunityCount: Number,
    busy: Boolean,
})
defineEmits(['navigate'])
</script>
<template>
    <nav aria-label="Navegação principal">
        <AppButton
            variant="ghost"
            v-for="item in items"
            :key="item.id"
            :class="{
                active:
                    activeId === item.id || (item.id === 'opportunities' && activeId === 'detail'),
            }"
            :disabled="busy"
            @click="$emit('navigate', item.id)"
        >
            <component :is="item.icon" :size="19" />{{ item.label
            }}<span v-if="item.id === 'opportunities' && opportunityCount" class="nav-count">{{
                opportunityCount
            }}</span>
        </AppButton>
    </nav>
</template>
