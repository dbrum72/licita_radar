import { defineComponent, h, reactive, ref } from 'vue'
import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import { provideWorkspace } from '@/composables/useWorkspace'
import { navigation } from '@/config/navigation'

export function createWorkspace() {
    const state = {
        user: ref({ name: 'Ana', email: 'ana@example.test' }),
        busy: ref(false),
        error: ref(''),
        message: ref(''),
        view: ref('dashboard'),
        title: ref('Painel'),
        nav: navigation,
        setup: ref(false),
        setupName: ref(''),
        login: reactive({ email: '', password: '' }),
        dashboard: ref({ runs: [], expiring: [], open: 0 }),
        profiles: ref([]),
        selectedProfileIds: ref([]),
        consultationProfiles: ref([]),
        profileForm: ref(null),
        documents: ref([]),
        reading: ref(null),
        chosenFile: ref(null),
        uploadKind: ref('attestation'),
        expires: ref(''),
        results: ref({ data: [], total: 0, current_page: 1, last_page: 1 }),
        filters: reactive({}),
        selectedIds: ref([]),
        detail: ref({
            opportunity: {
                id: 1,
                object: 'Projeto de arquitetura',
                scope_status: 'relevant',
                documents: [],
                requirements: [],
                attachments_status: 'pending',
                company_match: { evidence: [] },
            },
            verdict: 'Revisão pendente',
            notice: 'Parecer preliminar',
        }),
        newReq: reactive({ source_document_id: '', source_page: 1, description: '' }),
        reviewForm: ref(null),
        pendingDelete: ref(null),
        fetchingAttachments: ref(false),
        isCoolingDown: vi.fn(() => false),
        retryMessage: vi.fn(() => ''),
        perform: vi.fn(async (action) => action()),
    }
    for (const action of [
        'signIn',
        'signOut',
        'go',
        'loadDashboard',
        'loadResults',
        'synchronize',
        'sendDigest',
        'editProfile',
        'toggleProfile',
        'saveProfile',
        'upload',
        'readDocument',
        'downloadDocument',
        'openDetail',
        'toggleBookmark',
        'toggleDiscard',
        'selectPage',
        'exportSelected',
        'refreshAttachments',
        'fetchAttachments',
        'deleteAttachment',
        'confirmDelete',
        'cancelDelete',
        'review',
        'saveReview',
        'addRequirement',
    ]) {
        state[action] = vi.fn()
    }
    return state
}

export function mountWorkspace(Component, state = createWorkspace(), options = {}) {
    const Host = defineComponent({
        setup() {
            provideWorkspace(state)
            return () => h(Component)
        },
    })
    const wrapper = mount(Host, options)
    return { wrapper, state }
}

export function button(wrapper, text) {
    const found = wrapper.findAll('button').find((element) => element.text().includes(text))
    if (!found) throw new Error('Botão não encontrado: ' + text)
    return found
}
