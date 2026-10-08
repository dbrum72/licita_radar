export const money = (value) =>
    value == null
        ? 'Valor não informado'
        : new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
export const date = (value) =>
    value
        ? /^\d{4}-\d{2}-\d{2}$/.test(value)
            ? value.split('-').reverse().join('/')
            : new Date(value).toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo' })
        : 'Não informado'
export const datetime = (value) =>
    value ? new Date(value).toLocaleString('pt-BR', { timeZone: 'America/Sao_Paulo' }) : '—'
