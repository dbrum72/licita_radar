import { api } from '@/services/http'

export const profilesApi = {
    list: () => api('profiles', { timeoutMs: 15000 }),
    save: (profile) =>
        api('profiles' + (profile.id ? '/' + profile.id : ''), {
            method: profile.id ? 'PUT' : 'POST',
            body: profile,
        }),
    sendDigest: () => api('digest', { method: 'POST' }),
}
