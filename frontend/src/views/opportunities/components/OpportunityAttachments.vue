<script setup>
import { AppFileUpload } from '@/components/forms'
import { AppCard, AppButton } from '@/components/ui'
import { FileText } from '@lucide/vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { uploadRejectionMessage } from '@/utils/upload'
const {
    detail,
    busy,
    error,
    isCoolingDown,
    retryMessage,
    fetchingAttachments,
    fetchAttachments,
    perform,
    refreshAttachments,
    chosenFile,
    upload,
    openDocument,
    deleteAttachment,
} = useWorkspace()
</script>
<template>
    <AppCard as="section" class="panel">
        <div class="panel-heading">
            <h3>Edital e anexos</h3>
            <AppButton
                variant="outline"
                class="secondary"
                @click="fetchAttachments"
                :disabled="busy || isCoolingDown('attachments:' + detail.opportunity.id)"
                :title="retryMessage('attachments:' + detail.opportunity.id)"
            >
                {{ fetchingAttachments ? 'Baixando PDFs…' : 'Buscar anexos no PNCP' }}
            </AppButton>
        </div>
        <p v-if="detail.opportunity.attachments_error" class="error">
            {{ detail.opportunity.attachments_error }}
        </p>
        <p class="meta">
            Busca de anexos:
            {{
                {
                    pending: 'Ainda não consultado',
                    running: 'Baixando PDFs',
                    ready: 'Concluída',
                    partial: 'Concluída com anexos para revisão',
                    empty: 'Nenhum anexo encontrado',
                    failed: 'Falha na busca',
                }[detail.opportunity.attachments_status] || detail.opportunity.attachments_status
            }}
        </p>
        <AppButton
            variant="ghost"
            class="text-button"
            :disabled="busy"
            @click="perform(refreshAttachments)"
        >
            Atualizar anexos
        </AppButton>
        <p v-if="detail.opportunity.attachments_status === 'pending'">
            Clique em Buscar anexos no PNCP para baixar os PDFs.
        </p>
        <p>
            Os anexos são buscados somente quando você solicita. Você também pode adicionar PDFs
            manualmente.
        </p>
        <form class="upload-form" @submit.prevent="upload(true)">
            <AppFileUpload
                accept="application/pdf"
                required
                label="PDF do edital"
                id="opportunityattachments-1"
                :model-value="chosenFile ? [chosenFile] : []"
                @update:model-value="chosenFile = $event[0] || null"
                :max-files="1"
                :max-file-size="20 * 1024 * 1024"
                :disabled="busy"
                @reject="error = uploadRejectionMessage($event)"
                @select="error = ''"
            /><AppButton variant="primary" type="submit" class="primary" :disabled="busy"
                >Adicionar PDF</AppButton
            >
        </form>
        <div v-for="doc in detail.opportunity.documents" :key="doc.id" class="activity-row">
            <FileText :size="20" />
            <a href="#" target="_blank" rel="noopener" @click.prevent="openDocument(doc, true)">
                <strong>{{ doc.name }}</strong>
            </a>
            <AppButton
                variant="ghost"
                class="text-button"
                :disabled="busy"
                @click="deleteAttachment(doc)"
            >
                Excluir anexo
            </AppButton>
        </div>
    </AppCard>
</template>
