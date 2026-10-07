let token = ''
export async function api(path, options = {}) {
  const { responseType, ...requestOptions } = options
  const form = options.body instanceof FormData
  const response = await fetch('/api/' + path, {
    ...requestOptions, credentials:'same-origin',
    headers: { Accept:'application/json', ...(form ? {} : {'Content-Type':'application/json'}), 'X-CSRF-TOKEN':token, ...options.headers },
    body: options.body ? (form ? options.body : JSON.stringify(options.body)) : undefined,
  })
  if (response.status === 204) return null
  if (response.ok && responseType === 'blob') return response.blob()
  const data = await response.json()
  if (!response.ok) {
    const error = new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Não foi possível concluir a operação.'))
    error.status = response.status; throw error
  }
  if (data.token) token = data.token
  return data
}
