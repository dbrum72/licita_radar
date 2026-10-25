// @vitest-environment node
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createSSRApp, h, ref } from 'vue'
import { renderToString } from '@vue/server-renderer'
import api from '@/services/http'
import { provideWorkspace } from '@/composables/useWorkspace'
import { useProfiles } from '@/composables/useProfiles'
import { useOpportunities } from '@/composables/useOpportunities'
import { useAsyncAction } from '@/composables/useAsyncAction'
import { withRefresh } from '@/services/operations'
import { date } from '@/utils/formatters'
import DashboardPage from '@/views/dashboard/DashboardPage.vue'
import OpportunityListPage from '@/views/opportunities/OpportunityListPage.vue'
import CompanyDocumentListPage from '@/views/documents/CompanyDocumentListPage.vue'
import SearchProfileListPage from '@/views/profiles/SearchProfileListPage.vue'
import OpportunityShowPage from '@/views/opportunities/OpportunityShowPage.vue'
import SearchProfileDialog from '@/views/profiles/components/SearchProfileDialog.vue'

vi.mock('@/services/http', () => ({ default: vi.fn() }))

beforeEach(() => vi.clearAllMocks())

async function render(Component, configure = () => {}) {
    const warnings = []
    const app = createSSRApp({
        setup() {
            const workspace = provideWorkspace()
            configure(workspace)
            return () => h(Component)
        },
    })
    app.config.warnHandler = (warning) => warnings.push(warning)
    const html = await renderToString(app)
    expect(warnings).toEqual([])
    return html
}

describe('views with shared reactive state', () => {
    it.each([DashboardPage, OpportunityListPage, CompanyDocumentListPage, SearchProfileListPage])(
        'renders $.__name without missing bindings',
        async (Component) => {
            expect(await render(Component)).toBeTruthy()
            expect(api).not.toHaveBeenCalled()
        },
    )

    it('renders detail and document modals using their own bindings', async () => {
        expect(
            await render(OpportunityShowPage, (w) => {
                w.detail.value = {
                    opportunity: {
                        id: 1,
                        object: 'Projeto arquitetônico',
                        documents: [],
                        requirements: [],
                        company_match: { evidence: [] },
                    },
                    notice: 'Revisão manual',
                }
            }),
        ).toContain('Projeto arquitetônico')
        expect(await render(SearchProfileDialog, (w) => w.editProfile())).toContain(
            'Palavras-chave a ignorar',
        )
    })

    it('offers manual email dispatch without sending during rendering', async () => {
        expect(await render(SearchProfileListPage)).toContain('Enviar resumos por e-mail')
        expect(api).not.toHaveBeenCalled()
    })
})

describe('manual domain actions', () => {
    it('envia somente os perfis escolhidos na ação explícita de consulta', async () => {
        const actions = useAsyncAction(() => {})
        const opportunities = useOpportunities({
            ...actions,
            profiles: ref([
                { id: 7, active: true },
                { id: 9, active: true },
            ]),
            view: ref('dashboard'),
            detail: ref(null),
            documents: ref([]),
            loadDashboard: vi.fn(),
        })
        opportunities.selectedProfileIds.value = [7]
        expect(api).not.toHaveBeenCalled()
        api.mockResolvedValueOnce({ data: { message: 'Concluída' } })
        await opportunities.synchronize()
        expect(api).toHaveBeenCalledWith('sync', {
            method: 'POST',
            timeout: 0,
            data: { profile_ids: [7] },
        })
    })
    it('clears private data and open dialogs after an expired session', async () => {
        let workspace
        await render(SearchProfileListPage, (state) => {
            workspace = state
            state.user.value = { name: 'Usuário' }
            state.pendingDelete.value = { id: 1, name: 'Edital' }
            state.documents.value = [{ id: 1 }]
            state.selectedIds.value = [1]
            state.editProfile()
        })
        await workspace.perform(() =>
            Promise.reject({ response: { status: 401, data: { message: 'Sessão expirada' } } }),
        )
        expect(workspace.user.value).toBeNull()
        expect(workspace.pendingDelete.value).toBeNull()
        expect(workspace.profileForm.value).toBeNull()
        expect(workspace.documents.value).toEqual([])
        expect(workspace.selectedIds.value).toEqual([])
    })

    it('saves inclusion and exclusion keywords and allows clearing them', async () => {
        const actions = useAsyncAction(() => {})
        const profiles = useProfiles(actions)
        profiles.editProfile({
            id: 2,
            name: 'Projetos',
            keywords: ['projeto'],
            excluded_keywords: ['obra'],
            states: [],
        })
        profiles.profileForm.value.excluded_keywords = ''
        api.mockResolvedValueOnce({ data: {} }).mockResolvedValueOnce({ data: [] })
        await profiles.saveProfile()
        expect(api).toHaveBeenCalledWith(
            'profiles/2',
            expect.objectContaining({
                method: 'PUT',
                data: expect.objectContaining({ keywords: ['projeto'], excluded_keywords: [] }),
            }),
        )
    })

    it('sends email only on explicit action', async () => {
        const actions = useAsyncAction(() => {})
        const profiles = useProfiles(actions)
        expect(api).not.toHaveBeenCalled()
        api.mockResolvedValueOnce({ data: { sent: 1, message: '1 resumo enviado' } })
        await profiles.sendDigest()
        expect(api).toHaveBeenCalledWith('digest', { method: 'POST', timeout: 0 })
        expect(actions.message.value).toBe('1 resumo enviado')
    })

    it('limits PDF selection to 50 and excludes discarded entries', () => {
        const actions = useAsyncAction(() => {})
        const opportunities = useOpportunities({
            ...actions,
            view: ref('opportunities'),
            detail: ref(null),
            documents: ref([]),
            loadDashboard: vi.fn(),
        })
        opportunities.results.value.data = Array.from({ length: 60 }, (_, id) => ({
            id,
            discarded: id === 0,
        }))
        opportunities.selectPage()
        expect(opportunities.selectedIds.value).toHaveLength(50)
        expect(opportunities.selectedIds.value).not.toContain(0)
    })

    it('refreshes after a failed consultation while preserving the original error', async () => {
        const original = new Error('PNCP indisponível')
        const refresh = vi.fn().mockRejectedValue(new Error('Atualização falhou'))
        await expect(withRefresh(() => Promise.reject(original), refresh)).rejects.toBe(original)
        expect(refresh).toHaveBeenCalledOnce()
    })

    it('preserves date-only document validity without shifting the day', () => {
        expect(date('2026-10-08')).toBe('08/10/2026')
    })
})
