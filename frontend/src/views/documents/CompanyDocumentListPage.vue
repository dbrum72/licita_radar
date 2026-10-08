<script setup>
import { AppFileUpload, AppSelect, AppInput } from '@/components/forms'
import { AppCard, AppButton } from '@/components/ui'
import { Files, Plus, FileText } from '@lucide/vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { labels } from '@/constants/radar'
import { date } from '@/utils/formatters'
import { uploadRejectionMessage } from '@/utils/upload'
const {
    busy,
    error,
    documents,
    uploadKind,
    expires,
    chosenFile,
    upload,
    readDocument,
    downloadDocument,
} = useWorkspace()
</script>

<template>
    <div class="page-heading">
        <div>
            <span class="eyebrow">ACERVO TÉCNICO</span>
            <h1>Documentos da empresa</h1>
            <p>Certidões, atestados, RRT e habilitação do CNPJ que orientam a busca e a análise.</p>
        </div>
    </div>
    <AppCard as="form" class="panel upload-form" @submit.prevent="upload()">
        <AppFileUpload
            accept="application/pdf"
            required
            label="Arquivo PDF"
            id="companydocumentlistpage-1"
            hint="Até 20 MB. PDFs digitalizados podem precisar de OCR."
            :model-value="chosenFile ? [chosenFile] : []"
            @update:model-value="chosenFile = $event[0] || null"
            :max-files="1"
            :max-file-size="20 * 1024 * 1024"
            :disabled="busy"
            @reject="error = uploadRejectionMessage($event)"
            @select="error = ''"
        /><AppSelect
            v-model="uploadKind"
            label="Tipo"
            id="companydocumentlistpage-2"
            :options="[
                { value: 'attestation', label: 'Atestado técnico' },
                { value: 'certificate', label: 'Certificação / certidão' },
                { value: 'rrt', label: 'Registro de Responsabilidade Técnica - RRT' },
                { value: 'cnpj', label: 'CNPJ' },
            ]"
        /><AppInput
            v-model="expires"
            type="date"
            label="Validade, se houver"
            id="companydocumentlistpage-3"
        /><AppButton variant="primary" type="submit" class="primary" :disabled="busy"
            ><Plus :size="17" />Adicionar documento</AppButton
        >
    </AppCard>
    <AppCard as="div" v-if="!documents.length" class="panel empty">
        <Files :size="34" />
        <h3>Seu acervo começa aqui</h3>
        <p>Adicione os PDFs da empresa para encontrar evidências em cada análise.</p>
    </AppCard>
    <AppCard as="div" class="panel document-list" v-else>
        <article v-for="doc in documents" :key="doc.id">
            <span class="file-icon"><FileText :size="22" /></span>
            <div>
                <strong>{{ doc.name }}</strong
                ><small
                    >{{ labels[doc.kind] }} · {{ doc.pages?.length || 0 }} páginas · Validade:
                    {{ date(doc.expires_at) }}</small
                ><small v-if="doc.error" class="danger-text">{{ doc.error }}</small>
            </div>
            <span :class="['badge', doc.status]">{{ labels[doc.status] }}</span
            ><AppButton variant="ghost" class="text-button" @click="readDocument(doc.id)"
                >Ler texto</AppButton
            ><AppButton
                variant="ghost"
                :disabled="busy"
                @click="downloadDocument(doc)"
                class="text-button"
            >
                Baixar
            </AppButton>
        </article>
    </AppCard>
</template>
