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

export function monthChoices(count = 24) {
  const out = []
  const d = new Date()
  d.setDate(1)
  for (let i = 0; i < count; i++) {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    out.push({ value: `${y}-${m}`, text: `${m}/${y}` })
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
