import { ref } from 'vue'
import { downloadBlob } from '@/services/download'
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation'
import { documentsApi } from '@/api/documents'
import { opportunitiesApi } from '@/api/opportunities'

export function useDocuments({ perform, busy, error, message, detail, refreshAttachments }) {
    const documents = ref([]),
        reading = ref(null),
        chosenFile = ref(null),
        uploadKind = ref('attestation'),
        expires = ref('')

    async function upload(tender = false) {
        await perform(async () => {
            if (!chosenFile.value) throw new Error('Selecione um arquivo PDF.')
            const body = new FormData()
            body.append('file', chosenFile.value)
            body.append('kind', tender ? 'tender' : uploadKind.value)
            if (expires.value && !tender) body.append('expires_at', expires.value)
            if (tender) body.append('opportunity_id', detail.value.opportunity.id)
            const d = await documentsApi.upload(body)
            chosenFile.value = null
            if (tender) detail.value = await opportunitiesApi.show(detail.value.opportunity.id)
            else documents.value = await documentsApi.list()
            message.value =
                d.status === 'ready'
                    ? 'PDF recebido e texto extraído.'
                    : d.status === 'needs_ocr'
                      ? 'PDF recebido. Há páginas sem texto suficiente; é necessário OCR/revisão.'
                      : d.error
        })
    }

    const { pendingDelete, requestDelete, cancelDelete, clearDelete } = useDeleteConfirmation(busy)
    function deleteAttachment(document) {
        if (busy.value) return
        if (error) error.value = ''
        requestDelete(document)
    }
    async function confirmDelete() {
        const doc = pendingDelete.value
        if (!doc) return
        await perform(async () => {
            await documentsApi.deleteTender(doc.id)
            if (reading.value?.source === 'tender' && reading.value?.id === doc.id)
                reading.value = null
            clearDelete()
            await refreshAttachments()
            message.value = 'Anexo e requisitos vinculados excluídos.'
        })
    }

    async function readDocument(id, tender = false) {
        await perform(async () => {
            reading.value = {
                ...(await documentsApi.read(id, tender)),
                source: tender ? 'tender' : 'company',
            }
        })
    }

    async function downloadDocument(doc, tender = false) {
        await perform(async () => {
            downloadBlob(await documentsApi.download(doc.id, tender), doc.name)
        })
    }

    return {
        upload,
        deleteAttachment,
        confirmDelete,
        cancelDelete,
        pendingDelete,
        readDocument,
        downloadDocument,
        documents,
        reading,
        chosenFile,
        uploadKind,
        expires,
    }
}
