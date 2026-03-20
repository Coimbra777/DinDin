/** Valores canónicos da API / base de dados (pt-BR na UI). */
export const TRANSACTION_TYPE_INCOME = 'income'
export const TRANSACTION_TYPE_EXPENSE = 'expense'

/** Garante income | expense para o payload (defesa contra índices ou strings legadas). */
export function normalizeTransactionType(value) {
  const v = value
  if (v === TRANSACTION_TYPE_INCOME || v === 0 || v === '0') {
    return TRANSACTION_TYPE_INCOME
  }
  if (v === TRANSACTION_TYPE_EXPENSE || v === 1 || v === '1') {
    return TRANSACTION_TYPE_EXPENSE
  }
  const s = typeof v === 'string' ? v.trim().toLowerCase() : ''
  if (s === TRANSACTION_TYPE_INCOME || s === 'receita') {
    return TRANSACTION_TYPE_INCOME
  }
  return TRANSACTION_TYPE_EXPENSE
}
