import { getToken, setToken, clearToken } from '@/services/token'
const baseUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api').replace(/\/+$/, '')
export async function api(path, options = {}) {
    const { timeoutMs = 0, ...requestOptions } = options
    if (!timeoutMs) return request(path, requestOptions)
    const controller = new AbortController()
    const timer = setTimeout(() => controller.abort(), timeoutMs)
    try {
        return await request(path, { ...requestOptions, signal: controller.signal })
    } catch (failure) {
        if (controller.signal.aborted) {
            const error = new Error(
                'A API não respondeu a tempo. Verifique se o backend está disponível e recarregue a página.',
            )
            error.code = 'API_TIMEOUT'
            throw error
        }
        throw failure
    } finally {
        clearTimeout(timer)
    }
}

async function request(path, options = {}) {
    const { responseType, ...requestOptions } = options
    const form = options.body instanceof FormData
    const token = getToken()
    const response = await fetch(baseUrl + '/' + path, {
        ...requestOptions,
        credentials: 'omit',
        headers: {
            Accept: 'application/json',
            ...(form ? {} : { 'Content-Type': 'application/json' }),
            ...(token ? { Authorization: 'Bearer ' + token } : {}),
            ...options.headers,
        },
        body: options.body ? (form ? options.body : JSON.stringify(options.body)) : undefined,
    })
    if (response.status === 401) clearToken()
    if (response.status === 204) return null
    if (response.ok && responseType === 'blob') return response.blob()
    let data
    try {
        data = await response.json()
    } catch {
        const error = new Error(
            'O servidor não retornou uma resposta válida. Atualize a tela para conferir o resultado antes de tentar novamente.',
        )
        error.status = response.status
        throw error
    }
    if (!response.ok) {
        const error = new Error(
            data?.errors
                ? Object.values(data.errors).flat().join(' ')
                : data?.message || 'Não foi possível concluir a operação.',
        )
        error.status = response.status
        error.fields = data?.errors || {}
        const retry = data?.retry_after ?? response.headers?.get?.('Retry-After')
        error.retryAfter =
            Number.isFinite(Number(retry)) && Number(retry) > 0
                ? Math.ceil(Number(retry))
                : Math.max(0, Math.ceil((Date.parse(retry) - Date.now()) / 1000)) || 60
        throw error
    }
    if (data?.access_token) setToken(data.access_token)
    return data
}
