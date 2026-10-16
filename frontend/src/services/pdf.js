export async function openPdfTab(load, page = 1) {
    const tab = window.open('', '_blank')
    if (!tab) throw new Error('Permita abrir uma nova aba para visualizar o PDF.')
    tab.opener = null
    try {
        const blob = await load()
        if (tab.closed) return
        const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
        const target = Number.isInteger(page) && page > 1 ? url + '#page=' + page : url
        tab.location.replace(target)
        setTimeout(() => URL.revokeObjectURL(url), 60000)
    } catch (failure) {
        tab.close()
        throw failure
    }
}
