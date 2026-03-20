<template>
  <v-dialog :value="value" max-width="480" content-class="finance-dialog-content" @input="$emit('input', $event)">
    <v-card class="rounded-lg">
      <v-card-title class="subtitle-1 font-weight-bold">
        {{ isEdit ? 'Editar cartão' : 'Novo cartão' }}
        <v-spacer />
        <v-btn icon @click="close"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-4">
        <v-text-field v-model="name" label="Nome do cartão" outlined dense :rules="[rules.required]" />
        <v-text-field
          :value="limitStr"
          label="Limite total (R$)"
          outlined
          dense
          placeholder="0,00"
          hint="Digite números; centavos = 2 últimos dígitos"
          persistent-hint
          inputmode="decimal"
          :rules="[rules.requiredAmount]"
          @input="onLimitInput"
          @blur="normalizeLimit"
        />
        <v-row dense>
          <v-col cols="6">
            <v-text-field
              v-model.number="closingDay"
              label="Fechamento (dia)"
              type="number"
              min="1"
              max="31"
              outlined
              dense
              :rules="[rules.day]"
            />
          </v-col>
          <v-col cols="6">
            <v-text-field
              v-model.number="dueDay"
              label="Vencimento (dia)"
              type="number"
              min="1"
              max="31"
              outlined
              dense
              :rules="[rules.day]"
            />
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions class="px-4 pb-4">
        <v-btn text color="secondary" @click="close">Cancelar</v-btn>
        <v-spacer />
        <v-btn color="primary" depressed :loading="saving" @click="save">Guardar</v-btn>
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

export default {
  name: 'CreditCardFormDialog',
  props: {
    value: { type: Boolean, default: false },
    apiBase: { type: String, required: true },
    card: { type: Object, default: null },
  },
  data() {
    return {
      name: '',
      limitStr: '',
      closingDay: 10,
      dueDay: 17,
      saving: false,
      rules: {
        required: (v) => (v && String(v).trim() !== '') || 'Obrigatório',
        requiredAmount: (v) => {
          const n = parseBRLDigitsToNumber(v)
          const m = Number.isNaN(n) ? parseCurrencyBRLInput(v) : n
          return (!Number.isNaN(m) && m > 0) || 'Valor inválido'
        },
        day: (v) => {
          const n = Number(v)
          return (n >= 1 && n <= 31) || 'Dia entre 1 e 31'
        },
      },
    }
  },
  computed: {
    isEdit() {
      return !!(this.card && this.card.id)
    },
  },
  watch: {
    value(v) {
      if (v) this.hydrate()
    },
  },
  methods: {
    formatAmountBR(num) {
      if (num == null || Number.isNaN(Number(num))) return ''
      return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(num))
    },
    hydrate() {
      if (this.isEdit) {
        this.name = this.card.name
        this.limitStr = this.formatAmountBR(this.card.limit)
        this.closingDay = this.card.closing_day
        this.dueDay = this.card.due_day
      } else {
        this.name = ''
        this.limitStr = ''
        this.closingDay = 10
        this.dueDay = 17
      }
    },
    onLimitInput(val) {
      this.limitStr = formatBRLDigitsAsTyping(val)
    },
    normalizeLimit() {
      let n = parseBRLDigitsToNumber(this.limitStr)
      if (Number.isNaN(n)) n = parseCurrencyBRLInput(this.limitStr)
      if (!Number.isNaN(n) && n > 0) this.limitStr = this.formatAmountBR(n)
    },
    close() {
      this.$emit('input', false)
    },
    async save() {
      this.normalizeLimit()
      let n = parseBRLDigitsToNumber(this.limitStr)
      if (Number.isNaN(n)) n = parseCurrencyBRLInput(this.limitStr)
      if (!this.name.trim() || Number.isNaN(n) || n <= 0) return
      if (this.closingDay < 1 || this.closingDay > 31 || this.dueDay < 1 || this.dueDay > 31) return

      this.saving = true
      const payload = {
        name: this.name.trim(),
        limit: n,
        closing_day: this.closingDay,
        due_day: this.dueDay,
      }
      try {
        if (this.isEdit) {
          await axios.put(`${this.apiBase}/credit-cards/${this.card.id}`, payload)
        } else {
          await axios.post(`${this.apiBase}/credit-cards`, payload)
        }
        this.$emit('saved')
        this.close()
      } catch (e) {
        const d = e.response && e.response.data
        const msg =
          (d && d.message) ||
          (d && d.errors && Object.values(d.errors)[0] && Object.values(d.errors)[0][0]) ||
          'Erro ao guardar.'
        this.$emit('error', msg)
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
