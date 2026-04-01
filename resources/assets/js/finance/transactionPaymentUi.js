/**
 * Regras de apresentação de pagamento (alinhadas à API; despesas apenas na UI).
 */
import { wholeDaysPastDue } from './format'
import {
  PAYMENT_STATUS_OVERDUE,
  PAYMENT_STATUS_PAID,
  PAYMENT_STATUS_PENDING,
  TRANSACTION_TYPE_EXPENSE,
  TRANSACTION_TYPE_INCOME,
} from './transactionTypes'

export function isExpenseTransaction(item) {
  return item && item.type === TRANSACTION_TYPE_EXPENSE
}

/** Estado efetivo devolvido pela API (pending | paid | overdue). */
export function resolveEffectivePaymentStatus(item) {
  if (!isExpenseTransaction(item)) return ''
  if (item.payment_status) return item.payment_status
  if (item.is_overdue) return PAYMENT_STATUS_OVERDUE
  return PAYMENT_STATUS_PENDING
}

export function isPaidEffectivePayment(item) {
  return resolveEffectivePaymentStatus(item) === PAYMENT_STATUS_PAID
}

export function paymentStatusLabelPt(status) {
  if (status === PAYMENT_STATUS_PAID) return 'Pago'
  if (status === PAYMENT_STATUS_OVERDUE) return 'Atrasado'
  return 'Pendente'
}

export function paymentStatusChipColorVuetify(status) {
  if (status === PAYMENT_STATUS_PAID) return 'success'
  if (status === PAYMENT_STATUS_OVERDUE) return 'error'
  return 'warning'
}

export function dueMetaColorForPaymentItem(item) {
  if (!isExpenseTransaction(item)) return 'secondary'
  return resolveEffectivePaymentStatus(item) === PAYMENT_STATUS_OVERDUE
    ? 'error'
    : 'secondary'
}

export function overdueDaysHintPt(item) {
  if (!isExpenseTransaction(item) || !item.due_date || isPaidEffectivePayment(item)) {
    return ''
  }
  const n = wholeDaysPastDue(item.due_date)
  if (n <= 0) return ''
  return n === 1 ? 'Atrasado há 1 dia' : `Atrasado há ${n} dias`
}

export function transactionTypeIcon(item) {
  return item && item.type === TRANSACTION_TYPE_INCOME
    ? 'mdi-cash-plus'
    : 'mdi-cash-minus'
}

export function transactionAvatarColor(item) {
  return item && item.type === TRANSACTION_TYPE_INCOME ? 'success' : 'error'
}
