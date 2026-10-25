import http from '@/services/http'

export const profilesApi = {
    list: async () => (await http('profiles')).data,
    save: async (profile) =>
        (
            await http('profiles' + (profile.id ? '/' + profile.id : ''), {
                method: profile.id ? 'PUT' : 'POST',
                data: profile,
            })
        ).data,
    sendDigest: async () => (await http('digest', { method: 'POST', timeout: 0 })).data,
}
