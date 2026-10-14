import { ref } from 'vue'
import { downloadBlob } from '@/services/download'
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation'
import { documentsApi } from '@/api/documents'
import { opportunitiesApi } from '@/api/opportunities'

export function useDocuments({ perform, busy, error, message, detail, refreshAttachments }) {
    const documents = ref([]),
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
            await documentsApi.upload(body)
            chosenFile.value = null
            if (tender) detail.value = await opportunitiesApi.show(detail.value.opportunity.id)
            else documents.value = await documentsApi.list()
            message.value = 'PDF armazenado.'
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
            clearDelete()
            await refreshAttachments()
            message.value = 'Anexo excluído.'
        })
    }

    async function downloadDocument(doc, tender = false) {
        await perform(async () => {
            downloadBlob(await documentsApi.download(doc.id, tender), doc.name)
        })
    }

    async function openDocument(doc, tender = false) {
        if (busy.value) return
        const tab = window.open('', '_blank')
        if (tab) tab.opener = null
        await perform(async () => {
            if (!tab) throw new Error('Permita abrir uma nova aba para visualizar o PDF.')
            try {
                const blob = await documentsApi.download(doc.id, tender)
                if (tab.closed) return
                const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
                tab.location.replace(url)
                setTimeout(() => URL.revokeObjectURL(url), 60000)
            } catch (failure) {
                tab.close()
                throw failure
            }
        })
    }

    return {
        upload,
        deleteAttachment,
        confirmDelete,
        cancelDelete,
        pendingDelete,
        downloadDocument,
        openDocument,
        documents,
        chosenFile,
        uploadKind,
        expires,
    }
}
