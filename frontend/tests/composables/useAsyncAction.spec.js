import { afterEach, expect, it, vi } from 'vitest'
import { effectScope } from 'vue'
import { useAsyncAction } from '@/composables/useAsyncAction'

afterEach(() => vi.useRealTimers())

it('keeps rate-limit waits isolated per action and never submits automatically', async () => {
    vi.useFakeTimers()
    const scope = effectScope()
    const actions = scope.run(() => useAsyncAction(() => {}))
    const blocked = vi.fn().mockRejectedValue({ status: 429, retryAfter: 3, message: 'Aguarde' })
    await actions.perform(blocked, 'consultation')
    expect(actions.isCoolingDown('consultation')).toBe(true)
    expect(actions.isCoolingDown('digest')).toBe(false)
    await actions.perform(blocked, 'consultation')
    expect(blocked).toHaveBeenCalledOnce()
    const manual = vi.fn()
    await actions.perform(manual, 'digest')
    expect(manual).toHaveBeenCalledOnce()
    vi.advanceTimersByTime(3000)
    expect(actions.isCoolingDown('consultation')).toBe(false)
    expect(blocked).toHaveBeenCalledOnce()
    scope.stop()
    expect(vi.getTimerCount()).toBe(0)
})

it('retains field errors and reports an expired authenticated session', async () => {
    const expired = vi.fn()
    const actions = useAsyncAction(expired)
    await actions.perform(() =>
        Promise.reject({
            status: 422,
            message: 'Nome inválido',
            fields: { name: ['Obrigatório'] },
        }),
    )
    expect(actions.fieldErrors.value.name).toEqual(['Obrigatório'])
    await actions.perform(() => Promise.reject({ status: 401, message: 'Entre novamente' }))
    expect(expired).toHaveBeenCalledOnce()
    expect(actions.fieldErrors.value).toEqual({})
})
