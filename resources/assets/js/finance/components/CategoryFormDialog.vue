<template>
  <v-dialog :value="value" max-width="420" content-class="finance-dialog-content" @input="$emit('input', $event)">
    <v-card class="rounded-lg">
      <v-card-title class="subtitle-1 font-weight-bold">
        {{ isEdit ? 'Editar categoria' : 'Nova categoria' }}
        <v-spacer />
        <v-btn icon @click="close"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-4">
        <v-text-field v-model="name" label="Nome" outlined dense :rules="[rules.required]" />
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

export default {
  name: 'CategoryFormDialog',
  props: {
    value: { type: Boolean, default: false },
    apiBase: { type: String, required: true },
    category: { type: Object, default: null },
  },
  data() {
    return {
      name: '',
      color: '',
      saving: false,
      rules: {
        required: (v) => (v && String(v).trim() !== '') || 'Obrigatório',
      },
    }
  },
  computed: {
    isEdit() {
      return !!(this.category && this.category.id)
    },
  },
  watch: {
    value(v) {
      if (v) this.hydrate()
    },
  },
  methods: {
    hydrate() {
      if (this.isEdit) {
        this.name = this.category.name
        this.color = this.category.color || ''
      } else {
        this.name = ''
        this.color = ''
      }
    },
    close() {
      this.$emit('input', false)
    },
    async save() {
      if (!this.name || !String(this.name).trim()) return
      this.saving = true
      const payload = { name: this.name.trim(), color: this.color || null }
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
