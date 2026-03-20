/**
 * Formatação de moeda brasileira (BRL) — uso em toda a UI financeira.
 * Ex.: formatCurrencyBRL(1250.5) → "R$ 1.250,50"
 */

export function formatCurrencyBRL(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'R$ 0,00'
  }
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(Number(value))
}

/** Alias legado */
export const formatBRL = formatCurrencyBRL

/**
 * Eixo de gráfico / rótulos curtos: sempre moeda BRL (compacta valores grandes).
 */
export function formatCurrencyBRLAxis(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'R$ 0'
  }
  const n = Number(value)
  const opts =
    Math.abs(n) >= 100_000
      ? { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 }
      : { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }
  return new Intl.NumberFormat('pt-BR', opts).format(n)
}

/**
 * Converte string digitada no padrão BR (1.250,50 ou 1250,50) em número.
 */
export function parseCurrencyBRLInput(str) {
  if (str === null || str === undefined) return NaN
  let s = String(str).trim()
  s = s.replace(/\s/g, '').replace(/^R\$\s*/i, '')
  if (!s) return NaN
  s = s.replace(/\./g, '').replace(',', '.')
  return Number.parseFloat(s)
}

/** Apenas dígitos (entrada mascarada em centavos). */
export function extractBrlCurrencyDigits(str) {
  return String(str ?? '').replace(/\D/g, '').slice(0, 14)
}

/**
 * Máscara moeda BR enquanto digita: dígitos = centavos (ex.: "1234" → "12,34").
 */
export function formatBRLDigitsAsTyping(digits) {
  const d = extractBrlCurrencyDigits(digits)
  if (!d) return ''
  const cents = parseInt(d, 10)
  if (!Number.isFinite(cents)) return ''
  const reais = cents / 100
  return new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(reais)
}

/** Converte string mascarada (dígitos=centavos) para valor em reais. */
export function parseBRLDigitsToNumber(displayOrDigits) {
  const d = extractBrlCurrencyDigits(displayOrDigits)
  if (!d) return NaN
  return parseInt(d, 10) / 100
}
