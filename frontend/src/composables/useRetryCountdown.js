import { computed, getCurrentScope, onScopeDispose, ref } from 'vue'

// This timer updates the UI only; it never resubmits a request.
export function useRetryCountdown() {
    const deadlines = ref({})
    const now = ref(Date.now())
    let timer
    let disposed = false

    const remaining = computed(() =>
        Object.fromEntries(
            Object.entries(deadlines.value).map(([key, deadline]) => [
                key,
                Math.max(0, Math.ceil((deadline - now.value) / 1000)),
            ]),
        ),
    )

    function tick() {
        now.value = Date.now()
        if (Object.values(remaining.value).every((seconds) => seconds === 0)) clearInterval(timer)
    }

    function start(key, seconds) {
        if (disposed) return
        const delay =
            Number.isFinite(Number(seconds)) && Number(seconds) > 0
                ? Math.ceil(Number(seconds))
                : 60
        deadlines.value = { ...deadlines.value, [key]: Date.now() + delay * 1000 }
        clearInterval(timer)
        tick()
        timer = setInterval(tick, 1000)
    }

    const isCoolingDown = (key) => (remaining.value[key] || 0) > 0
    const retryMessage = (key) =>
        isCoolingDown(key) ? `Aguarde ${remaining.value[key]} segundos para tentar novamente.` : ''

    if (getCurrentScope())
        onScopeDispose(() => {
            disposed = true
            clearInterval(timer)
        })
    return { start, isCoolingDown, retryMessage }
}
