<template>
  <v-dialog :value="value" max-width="460" content-class="finance-dialog-content" @input="$emit('input', $event)">
    <v-card class="rounded-lg">
      <v-card-title class="subtitle-1 font-weight-bold">
        {{ isEdit ? 'Editar categoria' : 'Nova categoria' }}
        <v-spacer />
        <v-btn icon @click="close"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-4">
        <v-text-field v-model="name" label="Nome" outlined dense :rules="[rules.required]" />
        <v-select
          v-model="type"
          :items="typeItems"
          label="Tipo"
          outlined
          dense
          item-text="text"
          item-value="value"
          :rules="[rules.required]"
          :disabled="typeLocked"
          :hint="typeLockedHint"
          :persistent-hint="typeLocked"
        />
        <v-select
          v-if="type === TX_EXPENSE"
          v-model="group"
          :items="groupItems"
          label="Subgrupo (despesa)"
          outlined
          dense
          clearable
          hint="Opcional: fixa, variável ou financeira"
          persistent-hint
          item-text="text"
          item-value="value"
        />
        <v-text-field v-model="color" label="Cor (hex, opcional)" outlined dense placeholder="#2563eb" hint="Ex: #43a047" persistent-hint />
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
import { TRANSACTION_TYPE_EXPENSE, TRANSACTION_TYPE_INCOME } from '../transactionTypes'

const GROUP_FIXED = 'fixa'
const GROUP_VARIABLE = 'variavel'
const GROUP_FINANCIAL = 'financeira'

export default {
  name: 'CategoryFormDialog',
  props: {
    value: { type: Boolean, default: false },
    apiBase: { type: String, required: true },
    category: { type: Object, default: null },
    /** Ao criar (não edição), pré-seleciona receita ou despesa (ex.: vindo do modal de transação). */
    initialType: { type: String, default: null },
  },
  data() {
    return {
      TX_EXPENSE: TRANSACTION_TYPE_EXPENSE,
      name: '',
      type: TRANSACTION_TYPE_EXPENSE,
      group: null,
      color: '',
      saving: false,
      typeItems: [
        { text: 'Receita', value: TRANSACTION_TYPE_INCOME },
        { text: 'Despesa', value: TRANSACTION_TYPE_EXPENSE },
      ],
      groupItems: [
        { text: 'Fixa', value: GROUP_FIXED },
        { text: 'Variável', value: GROUP_VARIABLE },
        { text: 'Financeira', value: GROUP_FINANCIAL },
      ],
      rules: {
        required: (v) => (v !== null && v !== undefined && String(v).trim() !== '') || 'Obrigatório',
      },
    }
  },
  computed: {
    isEdit() {
      return !!(this.category && this.category.id)
    },
    typeLocked() {
      return this.isEdit && !!this.category && !!this.category.has_transactions
    },
    typeLockedHint() {
      return this.typeLocked
        ? 'O tipo não pode ser alterado enquanto existirem transações nesta categoria.'
        : ''
    },
  },
  watch: {
    value(v) {
      if (v) this.hydrate()
    },
    type(t) {
      if (t === TRANSACTION_TYPE_INCOME) {
        this.group = null
      }
    },
  },
  methods: {
    hydrate() {
      if (this.isEdit) {
        this.name = this.category.name
        this.color = this.category.color || ''
        this.type = this.category.type || TRANSACTION_TYPE_EXPENSE
        this.group = this.category.group || null
      } else {
        this.name = ''
        this.color = ''
        const allowed = [TRANSACTION_TYPE_INCOME, TRANSACTION_TYPE_EXPENSE]
        this.type = allowed.includes(this.initialType) ? this.initialType : TRANSACTION_TYPE_EXPENSE
        this.group = null
      }
    },
    close() {
      this.$emit('input', false)
    },
    async save() {
      if (!this.name || !String(this.name).trim()) return
      if (!this.type) return
      this.saving = true
      const payload = {
        name: this.name.trim(),
        color: this.color || null,
        type: this.type,
        group: this.type === TRANSACTION_TYPE_EXPENSE ? this.group || null : null,
      }
      try {
        if (this.isEdit) {
          await axios.put(`${this.apiBase}/categories/${this.category.id}`, payload)
        } else {
          await axios.post(`${this.apiBase}/categories`, payload)
        }
        this.$emit('saved')
        this.close()
      } catch (e) {
        const d = e.response && e.response.data
        const msg =
          (d && d.message) || (d && d.errors && Object.values(d.errors)[0] && Object.values(d.errors)[0][0]) || 'Erro ao guardar.'
        this.$emit('error', msg)
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
