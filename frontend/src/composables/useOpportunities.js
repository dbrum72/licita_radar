import { ref, reactive } from 'vue'
import { documentsApi } from '@/api/documents'
import { opportunitiesApi } from '@/api/opportunities'
import { withRefresh } from '@/services/operations'
import { downloadBlob } from '@/services/download'
import { useConsultationProfiles } from '@/composables/useConsultationProfiles'

export function useOpportunities({
    perform,
    message,
    view,
    detail,
    documents,
    loadDashboard,
    profiles = ref([]),
}) {
    const { selectedProfileIds, consultationProfiles } = useConsultationProfiles(profiles)
    const results = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
    const filters = reactive({
        keyword: '',
        state: '',
        agency: '',
        modality: '',
        scope: '',
        min_value: '',
        max_value: '',
        deadline: '',
        bookmarked: false,
        discarded: false,
    })
    const selectedIds = ref([]),
        fetchingAttachments = ref(false)

    async function loadResults(page = 1) {
        const p = new URLSearchParams()
        for (const [k, v] of Object.entries(filters))
            if (v !== '' && v !== false) p.set(k, v === true ? '1' : v)
        p.set('page', page)
        results.value = await opportunitiesApi.list(p)
    }

    async function synchronize() {
        await perform(
            () =>
                withRefresh(
                    async () => {
                        const data = await opportunitiesApi.consult([...selectedProfileIds.value])
                        message.value = data.message
                    },
                    async () => {
                        await loadDashboard()
                        if (view.value === 'opportunities') await loadResults()
                    },
                ),
            'consultation',
        )
    }

    async function refreshAttachments() {
        const id = detail.value?.opportunity.id
        if (!id || view.value !== 'detail') return
        const updated = await opportunitiesApi.show(id)
        if (view.value === 'detail' && detail.value?.opportunity.id === id) detail.value = updated
    }

    async function fetchAttachments() {
        await perform(async () => {
            fetchingAttachments.value = true
            try {
                await withRefresh(async () => {
                    const data = await opportunitiesApi.attachments(detail.value.opportunity.id)
                    message.value = data.message
                }, refreshAttachments)
            } finally {
                fetchingAttachments.value = false
            }
        }, 'attachments:' + detail.value?.opportunity.id)
    }

    async function openDetail(id) {
        await perform(async () => {
            detail.value = await opportunitiesApi.show(id)
            documents.value = await documentsApi.list()
            view.value = 'detail'
        })
    }

    async function toggleBookmark(item) {
        await perform(async () => {
            const data = await opportunitiesApi.bookmark(item.id, !item.bookmarked)
            item.bookmarked = data.bookmarked
            selectedIds.value = selectedIds.value.filter((id) => id !== item.id)
            await loadResults(results.value.current_page)
            if (!results.value.data.length && results.value.current_page > 1)
                await loadResults(results.value.current_page - 1)
            await loadDashboard()
            message.value = data.bookmarked
                ? 'Oportunidade salva. Consulte no filtro Salvas.'
                : 'Oportunidade devolvida à lista de pendentes.'
        })
    }

    async function toggleDiscard(item) {
        await perform(async () => {
            const discarded = !item.discarded
            await opportunitiesApi.discard(item.id, discarded)
            selectedIds.value = selectedIds.value.filter((id) => id !== item.id)
            await loadResults(results.value.current_page)
            if (!results.value.data.length && results.value.current_page > 1)
                await loadResults(results.value.current_page - 1)
            await loadDashboard()
            message.value = discarded
                ? 'Oportunidade descartada. Pode ser restaurada antes do vencimento; depois será excluída.'
                : 'Oportunidade restaurada para avaliação. Ela aparecerá se continuar compatível com o acervo e com os filtros.'
        })
    }

    function selectPage() {
        const ids = results.value.data.filter((item) => !item.discarded).map((item) => item.id)
        selectedIds.value = [...new Set([...selectedIds.value, ...ids])].slice(0, 50)
    }

    async function exportSelected() {
        await perform(async () => {
            const blob = await opportunitiesApi.export(selectedIds.value)
            downloadBlob(blob, 'licitaradar-oportunidades.pdf')
            message.value =
                'PDF gerado com ' + selectedIds.value.length + ' oportunidades selecionadas.'
        })
    }

    return {
        selectedProfileIds,
        consultationProfiles,
        loadResults,
        synchronize,
        refreshAttachments,
        fetchAttachments,
        openDetail,
        toggleBookmark,
        toggleDiscard,
        selectPage,
        exportSelected,
        results,
        filters,
        selectedIds,
        fetchingAttachments,
    }
}
