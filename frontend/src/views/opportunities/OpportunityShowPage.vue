<script setup>
import { AppButton } from '@/components/ui'
import { ChevronLeft, ArrowUpRight } from '@lucide/vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { labels } from '@/constants/radar'
import { date, money } from '@/utils/formatters'
import OpportunityVerdict from './components/OpportunityVerdict.vue'
import OpportunityAttachments from './components/OpportunityAttachments.vue'
import DeleteAttachmentDialog from './components/DeleteAttachmentDialog.vue'
const { detail, go } = useWorkspace()
</script>
<template>
    <AppButton variant="ghost" class="text-button back" @click="go('opportunities')">
        <ChevronLeft :size="16" />Voltar às oportunidades
    </AppButton>
    <div class="detail-heading">
        <span :class="['badge', detail.opportunity.scope_status]">{{
            labels[detail.opportunity.scope_status]
        }}</span>
        <h1>{{ detail.opportunity.object }}</h1>
        <p>
            {{ detail.opportunity.agency }} · {{ detail.opportunity.city }} /
            {{ detail.opportunity.state }}
        </p>
        <div class="detail-meta">
            <span>{{ money(detail.opportunity.estimated_value) }}</span
            ><span>Prazo: {{ date(detail.opportunity.deadline) }}</span
            ><a :href="detail.opportunity.pncp_url" target="_blank" rel="noopener"
                >Abrir no PNCP<ArrowUpRight :size="16"
            /></a>
        </div>
    </div>
    <OpportunityVerdict /><OpportunityAttachments /><DeleteAttachmentDialog />
</template>
