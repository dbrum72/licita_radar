import { ref } from 'vue'

export function useDeleteConfirmation(busy) {
    const pendingDelete = ref(null)
    const requestDelete = (item) => {
        if (!busy.value) pendingDelete.value = item
    }
    const cancelDelete = () => {
        if (!busy.value) pendingDelete.value = null
    }
    const clearDelete = () => {
        pendingDelete.value = null
    }
    return { pendingDelete, requestDelete, cancelDelete, clearDelete }
}
