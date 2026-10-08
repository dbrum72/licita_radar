import { ref, reactive, onMounted } from 'vue'
import { authApi } from '@/api/auth'

export function useAuth({
    perform,
    user,
    error,
    view,
    detail,
    selectedIds,
    loadDashboard,
    onLogout,
}) {
    const login = reactive({ email: '', password: '' }),
        setup = ref(false),
        setupName = ref(''),
        booting = ref(true)

    async function signIn() {
        await perform(async () => {
            const data = await (setup.value
                ? authApi.setup({ ...login, name: setupName.value })
                : authApi.login({ ...login }))
            user.value = data.user
            setup.value = false
            login.password = ''
            await loadDashboard()
        }, 'auth')
    }

    async function signOut() {
        await perform(async () => {
            try {
                await authApi.logout()
            } finally {
                onLogout?.()
                user.value = null
                detail.value = null
                selectedIds.value = []
                view.value = 'dashboard'
            }
        })
    }
    onMounted(async () => {
        try {
            const s = await authApi.initialize()
            setup.value = s.setup
            try {
                user.value = await authApi.current()
                booting.value = false
                await loadDashboard()
            } catch (e) {
                if (e.status === 401) onLogout?.()
                if (e.status !== 401) error.value = e.message
            }
        } catch (e) {
            error.value = e.message
        } finally {
            booting.value = false
        }
    })
    return { signIn, signOut, login, setup, setupName, booting }
}
