import { apiErrorMessage } from '@/utils/apiErrorMessage'
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
            error.value = await apiErrorMessage(failure)
            fieldErrors.value = failure.response?.data?.errors || {}
            if (failure.response?.status === 429 && key) {
                const retry =
                    failure.response.data?.retry_after ?? failure.response.headers?.['retry-after']
                const seconds =
                    Number(retry) > 0
                        ? Math.ceil(Number(retry))
                        : Math.ceil((Date.parse(retry) - Date.now()) / 1000)
                retries.start(key, seconds > 0 ? seconds : 60)
            }
            if (failure.response?.status === 401) onUnauthorized()
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
