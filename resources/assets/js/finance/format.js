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
 * Soma meses a um YYYY-MM (primeiro dia do mês).
 */
export function addMonthsToYearMonth(ym, delta) {
  if (!ym || !/^\d{4}-\d{2}$/.test(String(ym))) return ''
  const [ys, ms] = String(ym)
    .split('-')
    .map((x) => parseInt(x, 10))
  const d = new Date(ys, ms - 1 + delta, 1)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

/**
 * Lista de meses para seletor (valor YYYY-MM; texto legível em pt-BR).
 * Ordem: mês atual, depois meses futuros (crescente), depois passados (decrescente).
 *
 * @param {number|{pastMonths?: number, futureMonths?: number}} spec
 *   - número N: N−1 meses passados + atual + meses futuros (padrão alinhado ao limite da API, ~5 anos)
 *   - objeto: `pastMonths` e `futureMonths` (contam só para além do mês atual)
 */
export function monthChoices(spec = 24) {
  const defaults = { pastMonths: 23, futureMonths: 60 }
  const { pastMonths, futureMonths } =
    typeof spec === 'number'
      ? { pastMonths: Math.max(0, spec - 1), futureMonths: defaults.futureMonths }
      : {
          pastMonths: spec.pastMonths ?? defaults.pastMonths,
          futureMonths: spec.futureMonths ?? defaults.futureMonths,
        }

  const longFmt = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' })
  const fmtEntry = (d) => {
    const y = d.getFullYear()
    const monthIndex = d.getMonth()
    const m = String(monthIndex + 1).padStart(2, '0')
    const value = `${y}-${m}`
    const longLabel = longFmt.format(new Date(y, monthIndex, 1))
    const text = longLabel.charAt(0).toUpperCase() + longLabel.slice(1)
    return { value, text, short: `${m}/${y}` }
  }

  const anchor = new Date()
  anchor.setDate(1)

  const seen = new Set()
  const out = []
  const pushUnique = (entry) => {
    if (seen.has(entry.value)) return
    seen.add(entry.value)
    out.push(entry)
  }

  pushUnique(fmtEntry(new Date(anchor)))

  let d = new Date(anchor)
  for (let i = 0; i < futureMonths; i += 1) {
    d.setMonth(d.getMonth() + 1)
    pushUnique(fmtEntry(new Date(d)))
  }

  d = new Date(anchor)
  for (let i = 0; i < pastMonths; i += 1) {
    d.setMonth(d.getMonth() - 1)
    pushUnique(fmtEntry(new Date(d)))
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
