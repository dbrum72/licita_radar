import { expect, it } from 'vitest'
import { nextTick, ref } from 'vue'
import { useConsultationProfiles } from '@/composables/useConsultationProfiles'

it('não aplica perfis implicitamente e retira seleções pausadas ou excluídas', async () => {
    const profiles = ref([
        { id: 1, active: true },
        { id: 2, active: false },
        { id: 3, active: true },
    ])
    const state = useConsultationProfiles(profiles)
    expect(state.selectedProfileIds.value).toEqual([])
    expect(state.consultationProfiles.value.map((profile) => profile.id)).toEqual([1, 3])
    state.selectedProfileIds.value = [1, 3]
    profiles.value = [
        { id: 1, active: false },
        { id: 3, active: true },
    ]
    await nextTick()
    expect(state.selectedProfileIds.value).toEqual([3])
    profiles.value = []
    await nextTick()
    expect(state.selectedProfileIds.value).toEqual([])
})
