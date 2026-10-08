<script setup>
import { AppCard, AppButton } from '@/components/ui'
import { useWorkspace } from '@/composables/useWorkspace'
const { detail, readDocument } = useWorkspace()
</script>
<template>
    <AppCard as="section" class="panel verdict">
        <span class="eyebrow">PARECER DOCUMENTAL PRELIMINAR</span>
        <h2>{{ detail.verdict }}</h2>
        <p>{{ detail.opportunity.scope_reason }}</p>
        <p>{{ detail.notice }}</p>
        <h3>Confronto do objeto com o acervo</h3>
        <p>{{ detail.opportunity.company_match?.reason }}</p>
        <div
            v-for="e in detail.opportunity.company_match?.evidence"
            :key="e.document_id + '-' + e.page"
            class="candidate"
        >
            <AppButton variant="ghost" class="text-button" @click="readDocument(e.document_id)">
                {{ e.name }} · página {{ e.page }}
            </AppButton>
            <p>{{ e.concepts.join(', ') }}</p>
            <p>{{ e.excerpt }}</p>
        </div>
    </AppCard>
</template>
