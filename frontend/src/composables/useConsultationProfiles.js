import { computed, ref, watch } from 'vue'

export function useConsultationProfiles(profiles) {
    const selectedProfileIds = ref([])
    const consultationProfiles = computed(() => profiles.value.filter((profile) => profile.active))
    watch(consultationProfiles, (available) => {
        const ids = new Set(available.map((profile) => profile.id))
        selectedProfileIds.value = selectedProfileIds.value.filter((id) => ids.has(id))
    })
    return { selectedProfileIds, consultationProfiles }
}
