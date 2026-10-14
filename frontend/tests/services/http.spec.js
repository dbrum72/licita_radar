import { afterEach, expect, it, vi } from 'vitest'
import { api } from '@/services/http'
import { clearToken, setToken, getToken } from '@/services/token'

afterEach(() => {
    vi.useRealTimers()
    clearToken()
    vi.unstubAllGlobals()
})

it('encerra a espera de inicialização quando a API não responde', async () => {
    vi.useFakeTimers()
    const fetch = vi.fn(
        (_url, options) =>
            new Promise((_resolve, reject) => {
                options.signal.addEventListener('abort', () =>
                    reject(new DOMException('Aborted', 'AbortError')),
                )
            }),
    )
    vi.stubGlobal('fetch', fetch)
    const result = expect(api('auth/status', { timeoutMs: 15000 })).rejects.toMatchObject({
        code: 'API_TIMEOUT',
        message: expect.stringContaining('backend'),
    })
    await vi.advanceTimersByTimeAsync(15000)
    await result
    expect(fetch.mock.calls[0][1].signal.aborted).toBe(true)
})

it('returns a useful error when a timeout proxy returns HTML', async () => {
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({
            status: 504,
            ok: false,
            json: () => Promise.reject(new SyntaxError('Unexpected token')),
        }),
    )
    await expect(api('sync', { method: 'POST' })).rejects.toMatchObject({
        status: 504,
        message: expect.stringContaining('Atualize a tela'),
    })
})

it('sends JWT Bearer without cookies after login', async () => {
    const fetch = vi
        .fn()
        .mockResolvedValueOnce({
            status: 200,
            ok: true,
            json: async () => ({ access_token: 'test-token' }),
        })
        .mockResolvedValueOnce({ status: 200, ok: true, json: async () => ({ sent: 0 }) })
    vi.stubGlobal('fetch', fetch)
    await api('login', {
        method: 'POST',
        body: { email: 'user@example.test', password: 'password' },
    })
    await api('digest', { method: 'POST' })
    expect(fetch).toHaveBeenLastCalledWith(
        'http://127.0.0.1:8000/api/digest',
        expect.objectContaining({
            credentials: 'omit',
            headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        }),
    )
    expect(fetch.mock.calls[1][1].headers).not.toHaveProperty('X-CSRF-TOKEN')
})

it('preserves Retry-After and field errors for the caller', async () => {
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({
            status: 429,
            ok: false,
            headers: new Headers({ 'Retry-After': '15' }),
            json: async () => ({ message: 'Aguarde', errors: { name: ['Inválido'] } }),
        }),
    )
    await expect(api('sync', { method: 'POST' })).rejects.toMatchObject({
        status: 429,
        retryAfter: 15,
        fields: { name: ['Inválido'] },
    })
})

it('clears an expired JWT and stops sending it on subsequent requests', async () => {
    setToken('expired-jwt')
    const fetch = vi.fn().mockResolvedValue({
        status: 401,
        ok: false,
        json: async () => ({ message: 'Unauthenticated.' }),
    })
    vi.stubGlobal('fetch', fetch)
    await expect(api('me')).rejects.toMatchObject({ status: 401 })
    expect(getToken()).toBe('')
    await expect(api('me')).rejects.toMatchObject({ status: 401 })
    expect(fetch.mock.calls[1][1].headers).not.toHaveProperty('Authorization')
})

it('downloads private files through the configured API with Bearer authentication', async () => {
    setToken('download-jwt')
    const blob = new Blob(['%PDF-test'], { type: 'application/pdf' })
    const fetch = vi.fn().mockResolvedValue({ status: 200, ok: true, blob: async () => blob })
    vi.stubGlobal('fetch', fetch)
    expect(await api('documents/1/download', { responseType: 'blob' })).toBe(blob)
    expect(fetch).toHaveBeenCalledWith(
        'http://127.0.0.1:8000/api/documents/1/download',
        expect.objectContaining({
            credentials: 'omit',
            headers: expect.objectContaining({ Authorization: 'Bearer download-jwt' }),
        }),
    )
})
