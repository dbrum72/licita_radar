export function uploadRejectionMessage({ reason }) {
    return reason === 'max-file-size'
        ? 'O PDF deve ter no máximo 20 MB.'
        : reason === 'max-files'
          ? 'Remova o arquivo selecionado antes de escolher outro PDF.'
          : 'Selecione um arquivo PDF válido.'
}
