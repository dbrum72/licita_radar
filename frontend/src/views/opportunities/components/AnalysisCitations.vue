<script setup>
import { useWorkspace } from '@/composables/useWorkspace'
defineProps({ references: { type: Array, default: () => [] } })
const { openAnalysisSource } = useWorkspace()
</script>
<template>
    <div v-for="(reference, index) in references" :key="index" class="analysis-citation">
        <a href="#" target="_blank" rel="noopener" @click.prevent="openAnalysisSource(reference)">
            {{ reference.source_name }} · página {{ reference.page }}
        </a>
        <p>“{{ reference.quote }}”</p>
        <small v-if="reference.verification !== 'text_match'"
            >Referência visual; confira no PDF original.</small
        >
    </div>
    <p v-if="!references.length" class="meta">Sem referência documental.</p>
</template>
