<template>
  <span class="currency-display" :class="textClass">{{ formatted }}</span>
</template>

<script>
import { formatCurrencyBRL } from '../currency'

export default {
  name: 'CurrencyDisplay',
  props: {
    /** Valor numérico em reais */
    value: { type: [Number, String], default: 0 },
    /** prefixo opcional: + / − */
    sign: { type: String, default: '' },
    /** emphasis visual (verde / vermelho) */
    tone: {
      type: String,
      default: '',
      validator: (v) => ['', 'income', 'expense', 'muted'].includes(v),
    },
  },
  computed: {
    formatted() {
      const base = formatCurrencyBRL(this.value)
      return this.sign ? `${this.sign} ${base}`.trim() : base
    },
    textClass() {
      if (this.tone === 'income') return 'success--text'
      if (this.tone === 'expense') return 'error--text'
      if (this.tone === 'muted') return 'text--secondary'
      return ''
    },
  },
}
</script>
