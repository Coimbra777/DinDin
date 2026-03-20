<template>
  <v-card
    :class="[
      'balance-card rounded-xl',
      elevationClass,
      cardToneClass,
      { 'pa-6': variant === 'hero', 'pa-4': variant !== 'hero' },
    ]"
    :dark="dark"
    flat
  >
    <div v-if="variant === 'hero'" class="balance-card__hero">
      <p class="text-body-2 mb-1" :class="subtitleClass">{{ title }}</p>
      <p class="text-h3 text-sm-h2 font-weight-black mb-0 balance-card__amount balance-card__amount--hero">
        <currency-display :value="amount" />
      </p>
      <p v-if="hint" class="text-caption mt-3 mb-0" :class="hintClass">{{ hint }}</p>
    </div>

    <div v-else-if="variant === 'highlight'" class="balance-card__highlight">
      <p class="text-body-1 font-weight-medium mb-1" :class="subtitleClass">{{ title }}</p>
      <p class="text-h4 font-weight-bold mb-0" :class="amountClass">
        <currency-display :value="amount" />
      </p>
      <p v-if="subtitle" class="text-caption mt-2 mb-0" :class="hintClass">{{ subtitle }}</p>
    </div>

    <div v-else class="d-flex align-center justify-space-between">
      <div>
        <p class="text-caption text-uppercase font-weight-medium mb-1" :class="metaClass">{{ title }}</p>
        <p class="text-h6 font-weight-bold mb-0" :class="amountClass">
          <currency-display :value="amount" />
        </p>
      </div>
      <v-avatar v-if="icon" :color="iconColor" size="44">
        <v-icon dark>{{ icon }}</v-icon>
      </v-avatar>
    </div>
  </v-card>
</template>

<script>
import CurrencyDisplay from './CurrencyDisplay.vue'

export default {
  name: 'BalanceCard',
  components: { CurrencyDisplay },
  props: {
    variant: {
      type: String,
      default: 'stat',
      validator: (v) => ['hero', 'stat', 'highlight'].includes(v),
    },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    hint: { type: String, default: '' },
    amount: { type: [Number, String], default: 0 },
    icon: { type: String, default: '' },
    /** 'balance' neutro | 'income' verde | 'expense' vermelho | 'spend' gradiente pode gastar */
    accent: {
      type: String,
      default: 'balance',
      validator: (v) => ['balance', 'income', 'expense', 'spend'].includes(v),
    },
  },
  computed: {
    /** Hero segue o tema global; “spend” mantém cartão escuro */
    dark() {
      if (this.accent === 'spend') return true
      if (this.variant === 'hero') return this.$vuetify.theme.dark
      return false
    },
    cardToneClass() {
      if (this.variant === 'hero' || this.accent === 'spend') return ''
      if (this.accent === 'income') return 'balance-card--tone-income'
      if (this.accent === 'expense') return 'balance-card--tone-expense'
      return 'balance-card--tone-neutral'
    },
    elevationClass() {
      if (this.variant === 'hero') return 'balance-card--hero elevation-6'
      if (this.accent === 'spend') return 'balance-card--spend white--text elevation-4'
      return 'elevation-1'
    },
    subtitleClass() {
      return this.dark ? 'opacity-90' : 'text--secondary'
    },
    hintClass() {
      return this.dark ? 'opacity-85' : 'text--secondary'
    },
    metaClass() {
      return this.dark ? 'opacity-90' : 'text--secondary'
    },
    amountClass() {
      if (this.accent === 'income') return 'finance-amount-income'
      if (this.accent === 'expense') return 'finance-amount-expense'
      return 'finance-amount-neutral'
    },
    iconColor() {
      if (this.accent === 'income') return 'success'
      if (this.accent === 'expense') return 'error'
      return 'secondary'
    },
  },
}
</script>

<style scoped>
.balance-card--hero {
  background: linear-gradient(155deg, #3d424d 0%, #353942 45%, #2c2f36 100%) !important;
  border: 1px solid rgba(255, 0, 0, 0.35) !important;
  box-shadow:
    0 8px 32px rgba(0, 0, 0, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.06) !important;
}
.balance-card--spend {
  background: linear-gradient(145deg, #2e7d32 0%, #388e3c 40%, #4caf50 100%) !important;
}
.balance-card--tone-income {
  background: linear-gradient(135deg, rgba(76, 175, 80, 0.14) 0%, rgba(53, 57, 66, 0.98) 100%) !important;
  border-color: rgba(76, 175, 80, 0.35) !important;
}
.balance-card--tone-expense {
  background: linear-gradient(135deg, rgba(255, 0, 0, 0.12) 0%, rgba(53, 57, 66, 0.98) 100%) !important;
  border-color: rgba(255, 0, 0, 0.35) !important;
}
.balance-card--tone-neutral {
  background: rgba(53, 57, 66, 0.98) !important;
}
.finance-amount-income {
  color: #4caf50 !important;
}
.finance-amount-expense {
  color: #ff0000 !important;
}
.balance-card__amount {
  line-height: 1.15;
}
.opacity-90 {
  opacity: 0.9;
}
.opacity-85 {
  opacity: 0.85;
}
</style>
