import { ref } from 'vue'
import { profilesApi } from '@/api/profiles'

export function useProfiles({ perform, message }) {
    const profiles = ref([]),
        profileForm = ref(null)

    function editProfile(p = null) {
        profileForm.value = p
            ? {
                  ...p,
                  keywords: (p.keywords || []).join(', '),
                  excluded_keywords: (p.excluded_keywords || []).join(', '),
                  states: p.states?.join(', ') || '',
                  modalities: p.modalities || [],
              }
            : {
                  name: '',
                  keywords: '',
                  excluded_keywords: '',
                  states: '',
                  modalities: [],
                  agency: '',
                  min_value: null,
                  max_value: null,
                  min_days: 3,
                  email: '',
                  active: true,
              }
    }

    async function saveProfile() {
        await perform(async () => {
            const p = profileForm.value
            const body = {
                ...p,
                keywords: p.keywords
                    .split(',')
                    .map((s) => s.trim())
                    .filter(Boolean),
                excluded_keywords: p.excluded_keywords
                    .split(',')
                    .map((s) => s.trim())
                    .filter(Boolean),
                states: p.states
                    .split(',')
                    .map((s) => s.trim().toUpperCase())
                    .filter(Boolean),
                min_value: p.min_value === '' ? null : p.min_value,
                max_value: p.max_value === '' ? null : p.max_value,
            }
            await profilesApi.save(body)
            profiles.value = await profilesApi.list()
            profileForm.value = null
            message.value = 'Perfil salvo. Selecione-o na consulta para aplicar seus filtros.'
        })
    }

    async function toggleProfile(p) {
        await perform(async () => {
            await profilesApi.save({ ...p, active: !p.active })
            profiles.value = await profilesApi.list()
        })
    }

    async function sendDigest() {
        await perform(async () => {
            const response = await profilesApi.sendDigest()
            message.value = response.message
        }, 'digest')
    }

    return { editProfile, saveProfile, toggleProfile, sendDigest, profiles, profileForm }
}
