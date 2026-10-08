<script setup>
import { AppSelect, AppInput, AppTextarea } from '@/components/forms'
import { AppCard, AppButton } from '@/components/ui'
import { FileText } from '@lucide/vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { labels } from '@/constants/radar'
const { detail, busy, readDocument, review, addRequirement, newReq } = useWorkspace()
</script>
<template>
    <AppCard as="section" class="panel requirements">
        <div class="panel-heading">
            <div>
                <h3>Requisitos técnicos e evidências</h3>
                <p>
                    Somente requisitos de qualificação técnica são extraídos. Os candidatos precisam
                    de revisão.
                </p>
            </div>
            <span>{{ detail.opportunity.requirements.length }} itens</span>
        </div>
        <div v-if="!detail.opportunity.requirements.length" class="empty compact">
            <FileText :size="28" />
            <p>Adicione um edital e cadastre as exigências que não forem extraídas.</p>
        </div>
        <article v-for="req in detail.opportunity.requirements" :key="req.id" class="requirement">
            <div class="requirement-top">
                <span :class="['badge', req.status]">{{ labels[req.status] }}</span
                ><AppButton
                    variant="ghost"
                    class="text-button"
                    @click="readDocument(req.source_document_id, true)"
                >
                    Edital · página {{ req.source_page }}
                </AppButton>
            </div>
            <h4>{{ req.description }}</h4>
            <p v-if="req.notes">Revisão: {{ req.notes }}</p>
            <p v-if="req.evidence">
                Evidência: {{ req.evidence.name }} · página {{ req.evidence_page }}
            </p>
            <details v-if="req.candidates.length">
                <summary>
                    {{ req.candidates.length }} evidências candidatas por palavras em comum
                </summary>
                <div v-for="c in req.candidates" class="candidate">
                    <AppButton
                        variant="ghost"
                        class="text-button"
                        @click="readDocument(c.document_id)"
                    >
                        {{ c.name }} · página {{ c.page }}
                    </AppButton>
                    <p>{{ c.excerpt }}</p>
                </div>
            </details>
            <p v-else class="meta">Nenhuma evidência candidata encontrada.</p>
            <AppButton variant="outline" class="secondary" @click="review(req)"
                >Revisar requisito</AppButton
            >
        </article>
        <form
            v-if="detail.opportunity.documents.length"
            class="manual-requirement"
            @submit.prevent="addRequirement"
        >
            <h3>Adicionar exigência não extraída</h3>
            <div class="filter-grid">
                <AppSelect
                    v-model="newReq.source_document_id"
                    required
                    label="Documento de origem"
                    id="opportunityrequirements-1"
                    :options="[
                        { value: '', label: 'Selecione' },
                        ...detail.opportunity.documents.map((d) => ({
                            value: d.id,
                            label: d.name,
                        })),
                    ]"
                /><AppInput
                    v-model="newReq.source_page"
                    type="number"
                    min="1"
                    required
                    label="Página"
                    id="opportunityrequirements-2"
                /><AppTextarea
                    class="wide"
                    v-model="newReq.description"
                    required
                    :maxlength="4000"
                    label="Exigência"
                    id="opportunityrequirements-3"
                />
            </div>
            <AppButton variant="primary" type="submit" class="primary" :disabled="busy"
                >Adicionar requisito</AppButton
            >
        </form>
    </AppCard>
</template>
