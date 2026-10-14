import { ref } from 'vue'
import { useRetryCountdown } from '@/composables/useRetryCountdown'
export function useAsyncAction(onUnauthorized) {
    const busy = ref(false),
        error = ref(''),
        message = ref('')
    const fieldErrors = ref({})
    const retries = useRetryCountdown()
    async function perform(action, key = '') {
        if (busy.value || (key && retries.isCoolingDown(key))) return
        busy.value = true
        error.value = ''
        message.value = ''
        fieldErrors.value = {}
        try {
            await action()
        } catch (failure) {
            error.value = failure.message
            fieldErrors.value = failure.fields || {}
            if (failure.status === 429 && key) retries.start(key, failure.retryAfter)
            if (failure.status === 401) onUnauthorized()
        } finally {
            busy.value = false
        }
    }
    return {
        busy,
        error,
        message,
        fieldErrors,
        perform,
        isCoolingDown: retries.isCoolingDown,
        retryMessage: retries.retryMessage,
    }
}
