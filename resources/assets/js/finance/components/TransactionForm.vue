<template>
  <v-dialog
    :value="value"
    max-width="560"
    scrollable
    content-class="finance-dialog-content"
    @input="$emit('input', $event)"
  >
    <v-card>
      <v-card-title class="headline font-weight-bold primary white--text py-4">
        <v-icon left dark>{{ isEdit ? 'mdi-pencil' : 'mdi-plus-circle-outline' }}</v-icon>
        {{ isEdit ? 'Editar transação' : 'Nova transação' }}
        <v-spacer />
        <v-btn icon dark @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-6">
        <v-form ref="form" v-model="valid" lazy-validation @submit.prevent="submit">
          <div class="mb-2 d-flex align-center flex-wrap">
            <span class="text-subtitle-2 font-weight-bold">Tipo de transação</span>
            <help-tooltip
              class="ml-1"
              text="Receitas são entradas de dinheiro; despesas são saídas. O tipo define quais categorias você pode escolher."
              aria-label="Ajuda: tipo de transação"
            />
            <v-chip small dark class="ml-2" :color="form.type === TX_INCOME ? 'success' : 'error'">
              <v-icon left x-small>mdi-check-bold</v-icon>
              {{ typeChoiceLabel }}
            </v-chip>
          </div>
          <p class="text-caption text--secondary mb-3">{{ typeChoiceHint }}</p>

          <v-row dense class="mb-4">
            <v-col cols="12" sm="6">
              <v-card
                elevation="0"
                tabindex="0"
                role="button"
                class="finance-type-card rounded-lg pa-4 text-center"
                :class="incomeCardClass"
                @click="setType(TX_INCOME)"
                @keydown.enter.prevent="setType(TX_INCOME)"
                @keydown.space.prevent="setType(TX_INCOME)"
              >
                <v-icon
                  v-if="form.type === TX_INCOME"
                  class="finance-type-card__check"
                  color="white"
                  size="22"
                >
                  mdi-check-decagram
                </v-icon>
                <v-icon size="36" :color="form.type === TX_INCOME ? 'white' : 'success'">mdi-arrow-up-bold-circle</v-icon>
                <div class="text-body-1 font-weight-bold mt-2" :class="form.type === TX_INCOME ? 'white--text' : 'success--text'">
                  Receita
                </div>
                <div
                  class="text-caption mt-1"
                  :class="form.type === TX_INCOME ? 'white--text' : 'text--secondary'"
                  style="opacity: 0.95"
                >
                  Entrada de dinheiro
                </div>
              </v-card>
            </v-col>
            <v-col cols="12" sm="6">
              <v-card
                elevation="0"
                tabindex="0"
                role="button"
                class="finance-type-card rounded-lg pa-4 text-center"
                :class="expenseCardClass"
                @click="setType(TX_EXPENSE)"
                @keydown.enter.prevent="setType(TX_EXPENSE)"
                @keydown.space.prevent="setType(TX_EXPENSE)"
              >
                <v-icon
                  v-if="form.type === TX_EXPENSE"
                  class="finance-type-card__check"
                  color="white"
                  size="22"
                >
                  mdi-check-decagram
                </v-icon>
                <v-icon size="36" :color="form.type === TX_EXPENSE ? 'white' : 'error'">mdi-arrow-down-bold-circle</v-icon>
                <div class="text-body-1 font-weight-bold mt-2" :class="form.type === TX_EXPENSE ? 'white--text' : 'error--text'">
                  Despesa
                </div>
                <div
                  class="text-caption mt-1"
                  :class="form.type === TX_EXPENSE ? 'white--text' : 'text--secondary'"
                  style="opacity: 0.95"
                >
                  Saída de dinheiro
                </div>
              </v-card>
            </v-col>
          </v-row>

          <v-text-field
            v-model="form.title"
            label="Título"
            outlined
            dense
            prepend-inner-icon="mdi-format-title"
            :rules="[rules.required]"
          />

          <v-text-field
            :value="amountDisplay"
            label="Valor (R$)"
            outlined
            dense
            placeholder="0,00"
            prepend-inner-icon="mdi-currency-brl"
            hint="Digite só números — os centavos são os 2 últimos dígitos (ex.: 1500 → 15,00)"
            persistent-hint
            inputmode="decimal"
            :rules="[rules.requiredAmount]"
            @input="onAmountInput"
            @blur="normalizeAmountDisplay"
          >
            <template slot="append-outer">
              <help-tooltip text="Informe o valor da transação" aria-label="Ajuda: valor" />
            </template>
          </v-text-field>

          <v-select
            v-model="form.category_id"
            :items="categoryItems"
            label="Categoria"
            outlined
            dense
            clearable
            prepend-inner-icon="mdi-shape-outline"
            item-text="name"
            item-value="id"
            hide-details="auto"
            class="mb-2"
          >
            <template slot="append-outer">
              <help-tooltip
                text="A categoria define se é receita ou despesa"
                aria-label="Ajuda: categoria"
              />
            </template>
          </v-select>

          <v-select
            v-if="form.type === TX_EXPENSE && creditCardItems.length"
            v-model="form.credit_card_id"
            :items="creditCardItems"
            label="Cartão de crédito (opcional)"
            outlined
            dense
            clearable
            prepend-inner-icon="mdi-credit-card-outline"
            item-text="name"
            item-value="id"
            hide-details="auto"
            class="mb-2"
          >
            <template slot="append-outer">
              <help-tooltip text="Use para compras parceladas ou fatura" aria-label="Ajuda: cartão de crédito" />
            </template>
          </v-select>

          <v-menu
            ref="menu"
            v-model="dateMenu"
            :close-on-content-click="false"
            transition="scale-transition"
            offset-y
            min-width="auto"
          >
            <template slot="activator" slot-scope="{ on, attrs }">
              <v-text-field
                v-model="form.transaction_date"
                label="Data"
                prepend-inner-icon="mdi-calendar"
                readonly
                outlined
                dense
                v-bind="attrs"
                :rules="[rules.required]"
                v-on="on"
              />
            </template>
            <v-date-picker
              v-model="form.transaction_date"
              locale="pt-br"
              first-day-of-week="0"
              @input="dateMenu = false"
            />
          </v-menu>

          <v-row dense class="mt-2">
            <v-col cols="6">
              <v-text-field
                :value="installmentNumberStr"
                label="Parcela atual (opc.)"
                outlined
                dense
                clearable
                hint="ex.: 3"
                persistent-hint
                prepend-inner-icon="mdi-numeric"
                hide-details="auto"
                inputmode="numeric"
                @input="onInstallmentNumberInput"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                :value="installmentOfStr"
                label="Total parcelas (opc.)"
                outlined
                dense
                clearable
                hint="ex.: 12"
                persistent-hint
                prepend-inner-icon="mdi-counter"
                hide-details="auto"
                inputmode="numeric"
                @input="onInstallmentOfInput"
              />
            </v-col>
          </v-row>

          <v-textarea
            v-model="form.description"
            label="Descrição (opcional)"
            outlined
            rows="3"
            prepend-inner-icon="mdi-text"
            hide-details
            class="mt-2"
          />
        </v-form>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-btn text color="secondary" @click="close">Cancelar</v-btn>
        <v-spacer />
        <v-btn :loading="saving" color="primary" depressed rounded large @click="submit">
          <v-icon left>mdi-content-save</v-icon>
          {{ isEdit ? 'Atualizar' : 'Guardar' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
import axios from 'axios'
import {
  formatBRLDigitsAsTyping,
  parseBRLDigitsToNumber,
  parseCurrencyBRLInput,
} from '../currency'
import {
  TRANSACTION_TYPE_EXPENSE,
  TRANSACTION_TYPE_INCOME,
  normalizeTransactionType,
} from '../transactionTypes'
import HelpTooltip from './HelpTooltip.vue'

const emptyForm = () => ({
  title: '',
  amount: null,
  type: TRANSACTION_TYPE_EXPENSE,
  category_id: null,
  credit_card_id: null,
  transaction_date: new Date().toISOString().slice(0, 10),
  description: '',
  installment_number: null,
  installment_of: null,
})

export default {
  name: 'TransactionForm',
  components: { HelpTooltip },
  props: {
    value: { type: Boolean, default: false },
    apiBase: { type: String, required: true },
    categories: { type: Array, default: () => [] },
    creditCards: { type: Array, default: () => [] },
    transaction: { type: Object, default: null },
  },
  data() {
    return {
      TX_INCOME: TRANSACTION_TYPE_INCOME,
      TX_EXPENSE: TRANSACTION_TYPE_EXPENSE,
      valid: true,
      saving: false,
      dateMenu: false,
      amountDisplay: '',
      installmentNumberStr: '',
      installmentOfStr: '',
      form: emptyForm(),
      rules: {
        required: (v) => (v !== null && v !== undefined && String(v).trim() !== '') || 'Obrigatório',
        requiredAmount: (v) => {
          const n = parseBRLDigitsToNumber(v)
          const m = Number.isNaN(n) ? parseCurrencyBRLInput(v) : n
          return (!Number.isNaN(m) && m > 0) || 'Valor inválido (digite números, ex.: 125050 → 1.250,50)'
        },
      },
    }
  },
  computed: {
    isEdit() {
      return !!(this.transaction && this.transaction.id)
    },
    /** Categorias compatíveis com o tipo (se a API enviar category.type). */
    categoryItems() {
      const t = normalizeTransactionType(this.form.type)
      return this.categories.filter((c) => {
        if (c.type == null || c.type === '') {
          return t === this.TX_EXPENSE
        }
        return c.type === t
      })
    },
    creditCardItems() {
      return this.creditCards || []
    },
    typeChoiceLabel() {
      return this.form.type === this.TX_INCOME ? 'Receita (entrada)' : 'Despesa (saída)'
    },
    typeChoiceHint() {
      return this.form.type === this.TX_INCOME
        ? 'O valor será tratado como receita e somado ao seu saldo.'
        : 'O valor será tratado como despesa e subtraído do seu saldo.'
    },
    incomeCardClass() {
      return this.form.type === this.TX_INCOME
        ? 'finance-type-card--active-income'
        : 'finance-type-card--idle'
    },
    expenseCardClass() {
      return this.form.type === this.TX_EXPENSE
        ? 'finance-type-card--active-expense'
        : 'finance-type-card--idle'
    },
  },
  watch: {
    value(val) {
      if (val) this.hydrate()
    },
    transaction: {
      handler() {
        if (this.value) this.hydrate()
      },
      deep: true,
    },
  },
  methods: {
    setType(type) {
      const t = normalizeTransactionType(type)
      const prev = this.form.category_id
      this.$set(this.form, 'type', t)
      if (t !== this.TX_EXPENSE) {
        this.form.credit_card_id = null
      }
      if (prev == null) return
      const cat = this.categories.find((c) => c.id === prev)
      if (cat && cat.type && cat.type !== t) {
        this.form.category_id = null
      }
    },
    formatAmountBR(num) {
      if (num == null || num === '' || Number.isNaN(Number(num))) return ''
      return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(Number(num))
    },
    digitsOnly(str, maxLen) {
      return String(str ?? '')
        .replace(/\D/g, '')
        .slice(0, maxLen)
    },
    onInstallmentNumberInput(v) {
      this.installmentNumberStr = this.digitsOnly(v, 3)
      this.syncInstallments()
    },
    onInstallmentOfInput(v) {
      this.installmentOfStr = this.digitsOnly(v, 4)
      this.syncInstallments()
    },
    syncInstallments() {
      const n = this.installmentNumberStr === '' ? null : parseInt(this.installmentNumberStr, 10)
      const o = this.installmentOfStr === '' ? null : parseInt(this.installmentOfStr, 10)
      this.form.installment_number = Number.isFinite(n) ? n : null
      this.form.installment_of = Number.isFinite(o) ? o : null
    },
    hydrate() {
      if (this.transaction && this.transaction.id) {
        this.form = {
          title: this.transaction.title,
          amount: this.transaction.amount,
          type: normalizeTransactionType(this.transaction.type),
          category_id: this.transaction.category_id,
          credit_card_id: this.transaction.credit_card_id || null,
          transaction_date: this.transaction.transaction_date,
          description: this.transaction.description || '',
          installment_number: this.transaction.installment_number ?? null,
          installment_of: this.transaction.installment_of ?? null,
        }
        this.amountDisplay = this.formatAmountBR(this.transaction.amount)
        this.installmentNumberStr =
          this.transaction.installment_number != null ? String(this.transaction.installment_number) : ''
        this.installmentOfStr =
          this.transaction.installment_of != null ? String(this.transaction.installment_of) : ''
      } else {
        this.form = emptyForm()
        this.amountDisplay = ''
        this.installmentNumberStr = ''
        this.installmentOfStr = ''
      }
      this.$nextTick(() => this.$refs.form && this.$refs.form.resetValidation())
    },
    onAmountInput(val) {
      this.amountDisplay = formatBRLDigitsAsTyping(val)
      const n = parseBRLDigitsToNumber(this.amountDisplay)
      this.form.amount = Number.isNaN(n) ? null : n
    },
    normalizeAmountDisplay() {
      let n = parseBRLDigitsToNumber(this.amountDisplay)
      if (Number.isNaN(n)) n = parseCurrencyBRLInput(this.amountDisplay)
      if (!Number.isNaN(n) && n >= 0) {
        this.amountDisplay = this.formatAmountBR(n)
        this.form.amount = n
      }
    },
    close() {
      this.$emit('input', false)
    },
    async submit() {
      this.normalizeAmountDisplay()
      if (!this.$refs.form.validate()) return

      let n = parseBRLDigitsToNumber(this.amountDisplay)
      if (Number.isNaN(n)) n = parseCurrencyBRLInput(this.amountDisplay)
      if (Number.isNaN(n) || n <= 0) return

      this.saving = true
      const payload = {
        title: this.form.title,
        amount: n,
        type: normalizeTransactionType(this.form.type),
        transaction_date: this.form.transaction_date,
        description: this.form.description || null,
        category_id: this.form.category_id || null,
        credit_card_id: this.form.type === this.TX_EXPENSE && this.form.credit_card_id ? this.form.credit_card_id : null,
      }
      const inN = this.form.installment_number
      const inO = this.form.installment_of
      if (inN != null && inN !== '' && inO != null && inO !== '') {
        payload.installment_number = Number(inN)
        payload.installment_of = Number(inO)
      }

      try {
        if (this.isEdit) {
          await axios.put(`${this.apiBase}/transactions/${this.transaction.id}`, payload)
        } else {
          await axios.post(`${this.apiBase}/transactions`, payload)
        }
        this.$emit('saved')
        this.close()
      } catch (e) {
        const d = e.response && e.response.data
        let msg =
          (d && d.message) ||
          (d && d.error) ||
          (typeof d === 'string' ? d : null) ||
          'Não foi possível guardar.'
        if (d && d.errors) {
          const first = Object.keys(d.errors)[0]
          if (first && d.errors[first][0]) msg = d.errors[first][0]
        }
        this.$emit('error', msg)
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<style scoped>
.finance-type-card {
  border-radius: 12px !important;
  position: relative;
  cursor: pointer;
  transition:
    box-shadow 0.2s ease,
    transform 0.15s ease,
    border-color 0.2s ease,
    background 0.2s ease;
  border: 2px solid transparent;
  user-select: none;
}
.finance-type-card:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.35) !important;
}
.finance-type-card--idle {
  background-color: rgba(53, 57, 66, 0.95) !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.2);
}
.finance-type-card--idle:hover {
  background-color: rgba(61, 66, 77, 0.98) !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
  border-color: rgba(255, 255, 255, 0.18) !important;
}
.finance-type-card--active-income {
  background: linear-gradient(145deg, #388e3c 0%, #4caf50 100%) !important;
  border-color: #2e7d32 !important;
  box-shadow: 0 8px 22px rgba(76, 175, 80, 0.45) !important;
}
.finance-type-card--active-expense {
  background: linear-gradient(145deg, #d50000 0%, #ff0000 100%) !important;
  border-color: #b71c1c !important;
  box-shadow: 0 8px 22px rgba(255, 0, 0, 0.4) !important;
}
.finance-type-card__check {
  position: absolute;
  top: 8px;
  right: 8px;
}

/* Alinha ícone de ajuda com campos outlined densos */
::v-deep .v-input__append-outer {
  align-self: center;
  margin-top: 2px;
}
</style>
