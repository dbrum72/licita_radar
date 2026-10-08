import { afterEach, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { defineComponent, ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { authApi } from '@/api/auth'

vi.mock('@/api/auth', () => ({
    authApi: { initialize: vi.fn(), current: vi.fn() },
}))

afterEach(() => vi.clearAllMocks())

it('libera a interface autenticada antes da carga do painel e apresenta sua falha', async () => {
    authApi.initialize.mockResolvedValue({ setup: false })
    authApi.current.mockResolvedValue({ name: 'Ana' })
    let rejectDashboard
    const pending = new Promise((_resolve, reject) => {
        rejectDashboard = reject
    })
    const user = ref(null)
    const error = ref('')
    let state
    const wrapper = mount(
        defineComponent({
            setup() {
                state = useAuth({
                    user,
                    error,
                    perform: vi.fn(),
                    view: ref('dashboard'),
                    detail: ref(null),
                    selectedIds: ref([]),
                    loadDashboard: () => pending,
                })
                return () =>
                    state.booting.value ? 'Carregando' : `${user.value.name}: ${error.value}`
            },
        }),
    )
    await flushPromises()
    expect(state.booting.value).toBe(false)
    expect(wrapper.text()).toBe('Ana:')
    rejectDashboard(new Error('A API não respondeu a tempo.'))
    await flushPromises()
    expect(wrapper.text()).toContain('A API não respondeu a tempo.')
    expect(user.value.name).toBe('Ana')
    wrapper.unmount()
})
