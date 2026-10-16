import { computed, ref } from 'vue'
import { documentAnalysisApi } from '@/api/documentAnalysis'
import { downloadBlob } from '@/services/download'
import { openPdfTab } from '@/services/pdf'

export function useDocumentAnalysis({ perform, message, detail }) {
    const generatingAnalysis = ref(false)
    const analysis = computed(
        () =>
            detail.value?.analysis || { configured: false, model: '', report: null, stale: false },
    )

    function update(id, state) {
        if (detail.value?.opportunity.id === id) detail.value.analysis = state
    }

    async function generateAnalysis() {
        const id = detail.value?.opportunity.id
        if (!id) return
        await perform(async () => {
            generatingAnalysis.value = true
            try {
                const state = await documentAnalysisApi.generate(id)
                update(id, state)
                message.value = state.cached
                    ? 'Relatório reutilizado. Nenhuma nova chamada à IA.'
                    : 'Relatório documental gerado. Confira as evidências e pendências.'
            } finally {
                generatingAnalysis.value = false
            }
        }, 'analysis:' + id)
    }

    async function refreshAnalysis() {
        const id = detail.value?.opportunity.id
        if (!id) return
        await perform(async () => update(id, await documentAnalysisApi.get(id)))
    }

    async function downloadAnalysis() {
        const id = detail.value?.opportunity.id
        if (!id) return
        await perform(async () =>
            downloadBlob(await documentAnalysisApi.download(id), `analise-oportunidade-${id}.pdf`),
        )
    }

    async function openAnalysisSource(reference) {
        const id = detail.value?.opportunity.id
        if (!id) return
        await perform(() =>
            openPdfTab(() => documentAnalysisApi.source(id, reference.source_key), reference.page),
        )
    }

    return {
        analysis,
        generatingAnalysis,
        generateAnalysis,
        refreshAnalysis,
        downloadAnalysis,
        openAnalysisSource,
    }
}
