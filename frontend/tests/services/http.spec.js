import { afterEach, beforeEach, expect, it, vi } from 'vitest'
import { AxiosError, AxiosHeaders } from 'axios'
import client from '@/services/http'
import { authApi } from '@/api/auth'
import { opportunitiesApi } from '@/api/opportunities'
import { apiErrorMessage } from '@/utils/apiErrorMessage'
import { clearToken, setToken, getToken } from '@/services/token'

const originalAdapter = client.defaults.adapter
let adapter

beforeEach(() => {
    adapter = vi.fn(async (config) => ({ data: {}, status: 200, headers: {}, config }))
    client.defaults.adapter = adapter
})

afterEach(() => {
    client.defaults.adapter = originalAdapter
    clearToken()
})

function respond(data, status = 200, headers = {}) {
    return async (config) => {
        const response = { data, status, headers: new AxiosHeaders(headers), config }
        if (status >= 400)
            throw new AxiosError('Request failed', 'ERR_BAD_RESPONSE', config, {}, response)
        return response
    }
}

it('usa o timeout de leitura e mantém ações manuais sem timeout de 15 segundos', async () => {
    adapter.mockRejectedValueOnce(new AxiosError('Timeout', 'ECONNABORTED'))
    await expect(client('auth/status', { timeout: 15000 })).rejects.toMatchObject({
        code: 'ECONNABORTED',
    })
    expect(adapter.mock.calls[0][0].timeout).toBe(15000)
    await opportunitiesApi.consult()
    expect(adapter.mock.calls[1][0].timeout).toBe(0)
    expect(client.defaults.timeout).toBe(15000)
})

it('preserva uma mensagem útil quando o proxy retorna HTML', async () => {
    adapter.mockImplementation(respond('<html>Gateway timeout</html>', 504))
    const error = await client.post('sync').catch((error) => error)
    expect(error.response.status).toBe(504)
    expect(await apiErrorMessage(error)).toContain('Atualize a tela')
})

it('envia JSON e JWT Bearer sem cookies ou tenant após o login', async () => {
    adapter.mockImplementationOnce(respond({ access_token: 'test-token' }))
    await authApi.login({ email: 'user@example.test', password: 'password' })
    await client('digest', { method: 'POST' })
    const config = adapter.mock.calls[1][0]
    expect(config.baseURL).toBe('http://127.0.0.1:8000/api')
    expect(config.url).toBe('digest')
    expect(config.withCredentials).not.toBe(true)
    expect(config.headers.get('Authorization')).toBe('Bearer test-token')
    expect(config.headers.has('X-Tenant')).toBe(false)
    expect(config.headers.has('X-CSRF-TOKEN')).toBe(false)
    expect(JSON.parse(adapter.mock.calls[0][0].data)).toEqual({
        email: 'user@example.test',
        password: 'password',
    })
})

it('preserva Retry-After e erros dos campos', async () => {
    adapter.mockImplementation(
        respond({ message: 'Aguarde', errors: { name: ['Inválido'] } }, 429, {
            'Retry-After': '15',
        }),
    )
    await expect(client('sync', { method: 'POST' })).rejects.toMatchObject({
        response: {
            status: 429,
            data: { errors: { name: ['Inválido'] } },
            headers: { 'Retry-After': '15' },
        },
    })
})

it('remove JWT expirado e deixa de enviá-lo nas chamadas seguintes', async () => {
    setToken('expired-jwt')
    adapter.mockImplementation(respond({ message: 'Unauthenticated.' }, 401))
    await expect(client('me')).rejects.toMatchObject({ response: { status: 401 } })
    expect(getToken()).toBe('')
    await expect(client('me')).rejects.toMatchObject({ response: { status: 401 } })
    expect(adapter.mock.calls[1][0].headers.has('Authorization')).toBe(false)
})

it('baixa arquivos privados com autenticação Bearer', async () => {
    setToken('download-jwt')
    const blob = new Blob(['%PDF-test'], { type: 'application/pdf' })
    adapter.mockImplementation(respond(blob))
    expect((await client('documents/1/download', { responseType: 'blob' })).data).toBe(blob)
    expect(adapter.mock.calls[0][0].responseType).toBe('blob')
    expect(adapter.mock.calls[0][0].headers.get('Authorization')).toBe('Bearer download-jwt')
})

it('decodifica erros JSON mesmo quando o download espera um blob', async () => {
    const blob = new Blob(['error'], { type: 'application/json' })
    blob.text = async () => JSON.stringify({ message: 'Arquivo ausente.' })
    adapter.mockImplementation(respond(blob, 404))
    const error = await client
        .get('documents/1/download', { responseType: 'blob' })
        .catch((error) => error)
    expect(error.response.status).toBe(404)
    expect(await apiErrorMessage(error)).toBe('Arquivo ausente.')
})

it('preserva FormData para uploads e mantém a resposta HTTP 204', async () => {
    const form = new FormData()
    form.append('file', new Blob(['%PDF-test']), 'edital.pdf')
    adapter.mockImplementation(respond('', 204))
    expect((await client('documents', { method: 'POST', data: form })).status).toBe(204)
    expect(adapter.mock.calls[0][0].data).toBe(form)
})

it('aplica autenticação e tratamento de erros também pelos métodos nativos do Axios', async () => {
    adapter.mockImplementationOnce(respond({ access_token: 'native-token' }))
    await authApi.setup({ email: 'user@example.test' })
    expect(getToken()).toBe('native-token')
    adapter.mockImplementationOnce(respond({ message: 'Sessão expirada.' }, 401))
    await expect(client.get('me')).rejects.toMatchObject({
        response: { status: 401, data: { message: 'Sessão expirada.' } },
    })
    expect(adapter.mock.calls[1][0].headers.get('Authorization')).toBe('Bearer native-token')
    expect(getToken()).toBe('')
})

it('não repete requisições que falham na rede', async () => {
    adapter.mockRejectedValue(new AxiosError('Network Error', 'ERR_NETWORK'))
    await expect(client('sync', { method: 'POST' })).rejects.toMatchObject({ code: 'ERR_NETWORK' })
    expect(adapter).toHaveBeenCalledTimes(1)
})
