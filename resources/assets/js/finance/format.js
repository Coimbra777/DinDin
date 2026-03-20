export { formatCurrencyBRL, formatCurrencyBRLAxis, formatBRL, parseCurrencyBRLInput } from './currency'

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
