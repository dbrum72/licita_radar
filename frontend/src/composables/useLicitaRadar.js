import { computed, ref } from 'vue'
import { navigation } from '@/config/navigation'
import { dashboardApi } from '@/api/dashboard'
import { documentsApi } from '@/api/documents'
import { profilesApi } from '@/api/profiles'
import { useAsyncAction } from '@/composables/useAsyncAction'
import { useAuth } from '@/composables/useAuth'
import { useProfiles } from '@/composables/useProfiles'
import { useDocuments } from '@/composables/useDocuments'
import { useOpportunities } from '@/composables/useOpportunities'

export function useLicitaRadar() {
    const user = ref(null),
        view = ref('dashboard'),
        detail = ref(null)
    const dashboard = ref({ runs: [], expiring: [] })
    const actions = useAsyncAction(clearPrivateState)
    const { perform, message, error } = actions
    const loadDashboard = async () => {
        dashboard.value = await dashboardApi.get()
        profiles.profiles.value = await profilesApi.list()
    }
    const profiles = useProfiles({ perform, message })
    const documents = useDocuments({
        perform,
        busy: actions.busy,
        error,
        message,
        detail,
        refreshAttachments: () => opportunities.refreshAttachments(),
    })
    const opportunities = useOpportunities({
        perform,
        message,
        view,
        detail,
        documents: documents.documents,
        loadDashboard,
        profiles: profiles.profiles,
    })
    const auth = useAuth({
        perform,
        user,
        error,
        view,
        detail,
        selectedIds: opportunities.selectedIds,
        loadDashboard,
        onLogout: clearPrivateState,
    })
    const nav = navigation
    const title = computed(
        () => nav.find((item) => item.id === view.value)?.label || 'Oportunidade',
    )
    function clearPrivateState() {
        user.value = null
        detail.value = null
        view.value = 'dashboard'
        documents.documents.value = []
        documents.pendingDelete.value = null
        documents.chosenFile.value = null
        profiles.profiles.value = []
        profiles.profileForm.value = null
        opportunities.results.value = { data: [], current_page: 1, last_page: 1, total: 0 }
        opportunities.selectedIds.value = []
        opportunities.selectedProfileIds.value = []
        dashboard.value = { runs: [], expiring: [] }
    }
    async function go(id) {
        if (actions.busy.value) return
        view.value = id
        detail.value = null
        documents.chosenFile.value = null
        if (id !== 'opportunities') opportunities.selectedIds.value = []
        await perform(async () => {
            if (id === 'dashboard') await loadDashboard()
            if (id === 'opportunities') {
                profiles.profiles.value = await profilesApi.list()
                await opportunities.loadResults()
            }
            if (id === 'documents') documents.documents.value = await documentsApi.list()
            if (id === 'profiles') profiles.profiles.value = await profilesApi.list()
        })
    }
    return {
        user,
        view,
        detail,
        dashboard,
        nav,
        title,
        go,
        loadDashboard,
        ...actions,
        ...auth,
        ...profiles,
        ...documents,
        ...opportunities,
    }
}
