export { formatCurrencyBRL, formatCurrencyBRLAxis, formatBRL, parseCurrencyBRLInput } from './currency'

/** Extrai YYYY-MM-DD de string da API (data só ou ISO 8601). */
export function toIsoDateOnly(value) {
  if (value == null || value === '') return ''
  const m = String(value).trim().match(/(\d{4})-(\d{2})-(\d{2})/)
  if (!m) return ''
  return `${m[1]}-${m[2]}-${m[3]}`
}

/** Exibe data no formato brasileiro DD/MM/AAAA. */
export function formatDatePtBR(value) {
  const iso = toIsoDateOnly(value)
  if (!iso) return ''
  const [y, m, d] = iso.split('-')
  return `${d}/${m}/${y}`
}

/** Data de hoje no fuso local, como YYYY-MM-DD (picker / API). */
export function todayIsoLocal() {
  const n = new Date()
  const y = n.getFullYear()
  const mo = String(n.getMonth() + 1).padStart(2, '0')
  const da = String(n.getDate()).padStart(2, '0')
  return `${y}-${mo}-${da}`
}

/**
 * Lista de meses para seletor (valor YYYY-MM; texto legível em pt-BR).
 * Ordem: mês atual primeiro, depois meses anteriores.
 */
export function monthChoices(count = 24) {
  const out = []
  const d = new Date()
  d.setDate(1)
  const longFmt = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' })
  for (let i = 0; i < count; i += 1) {
    const y = d.getFullYear()
    const monthIndex = d.getMonth()
    const m = String(monthIndex + 1).padStart(2, '0')
    const value = `${y}-${m}`
    const longLabel = longFmt.format(new Date(y, monthIndex, 1))
    const text = longLabel.charAt(0).toUpperCase() + longLabel.slice(1)
    out.push({
      value,
      text,
      short: `${m}/${y}`,
    })
    d.setMonth(d.getMonth() - 1)
  }
  return out
}

export function normalizeMonth(value) {
  if (!value || !/^\d{4}-\d{2}$/.test(value)) {
    const n = new Date()
    return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}`
  }
  return value
}
