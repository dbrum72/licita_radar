export async function apiErrorMessage(error) {
    if (['ECONNABORTED', 'ETIMEDOUT'].includes(error.code))
        return 'A API não respondeu a tempo. Verifique se o backend está disponível e recarregue a página.'
    if (!error.response) return error.message

    let { data } = error.response
    if (data instanceof Blob) {
        try {
            data = JSON.parse(await data.text())
        } catch {
            data = null
        }
    }
    if (data?.errors) return Object.values(data.errors).flat().join(' ')
    return (
        data?.message ||
        'O servidor não retornou uma resposta válida. Atualize a tela para conferir o resultado antes de tentar novamente.'
    )
}
