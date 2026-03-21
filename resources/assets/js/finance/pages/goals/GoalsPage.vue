<template>
  <div class="goals-page px-1">
    <p class="text-caption secondary--text mb-4">
      Metas com valor alvo e prazo. Opcionalmente vincule uma categoria de receita para atualizar o progresso com base nos lançamentos.
    </p>

    <v-card class="rounded-xl mb-4" flat outlined>
      <v-card-title class="subtitle-1 font-weight-bold py-3">
        <v-icon left color="primary">mdi-flag-checkered</v-icon>
        Suas metas
        <v-spacer />
        <v-btn color="primary" depressed rounded small class="text-none" @click="openCreate">
          <v-icon left small>mdi-plus</v-icon>
          Nova meta
        </v-btn>
      </v-card-title>
      <v-divider />

      <v-card-text v-if="loading && !goals.length" class="text-center py-10">
        <v-progress-circular indeterminate color="primary" />
      </v-card-text>
      <v-card-text v-else-if="!goals.length" class="finance-text-muted text-body-2 py-10 text-center">
        Nenhuma meta ainda. Crie uma para acompanhar objetivos financeiros.
      </v-card-text>
      <v-list v-else class="py-0 transparent" two-line>
        <template v-for="(g, i) in goals">
          <v-list-item :key="g.id" class="px-2">
            <v-list-item-content>
              <v-list-item-title class="font-weight-medium">{{ g.title }}</v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                Prazo {{ formatDate(g.deadline) }}
                <span v-if="g.income_category"> · {{ g.income_category.name }}</span>
              </v-list-item-subtitle>
              <div class="mt-2">
                <div class="d-flex justify-space-between text-caption mb-1">
                  <span>{{ formatBRL(g.current_amount) }} / {{ formatBRL(g.target_amount) }}</span>
                  <span class="font-weight-medium">{{ g.progress_percent }}%</span>
                </div>
                <v-progress-linear
                  :value="Math.min(100, g.progress_percent)"
                  color="primary"
                  height="8"
                  rounded
                />
              </div>
            </v-list-item-content>
            <v-list-item-action class="flex-row mx-0">
              <v-btn icon small aria-label="Remover meta" @click="askDelete(g)">
                <v-icon small color="secondary">mdi-delete-outline</v-icon>
              </v-btn>
            </v-list-item-action>
          </v-list-item>
          <v-divider v-if="i < goals.length - 1" :key="'d' + g.id" class="mx-2 opacity-35" />
        </template>
      </v-list>
    </v-card>

    <v-dialog v-model="dialog.open" max-width="480" persistent>
      <v-card class="rounded-lg">
        <v-card-title class="headline">Nova meta</v-card-title>
        <v-card-text class="pt-4">
          <v-text-field v-model="dialog.form.title" label="Título" outlined dense hide-details="auto" class="mb-3" />
          <v-text-field
            v-model="dialog.form.target_amount"
            label="Valor alvo (R$)"
            outlined
            dense
            type="number"
            min="0.01"
            step="0.01"
            hide-details="auto"
            class="mb-3"
          />
          <v-text-field v-model="dialog.form.deadline" type="date" label="Prazo" outlined dense hide-details="auto" class="mb-3" />
          <v-select
            v-model="dialog.form.income_category_id"
            :items="incomeCategories"
            label="Categoria de receita (opcional)"
            item-text="name"
            item-value="id"
            outlined
            dense
            clearable
            hide-details="auto"
            hint="Usada para somar receitas e calcular progresso automaticamente."
            persistent-hint
          />
        </v-card-text>
        <v-card-actions>
          <v-btn text @click="dialog.open = false">Cancelar</v-btn>
          <v-spacer />
          <v-btn color="primary" depressed :loading="dialog.saving" :disabled="!canSave" @click="saveCreate">Guardar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDlg.open" max-width="400" persistent>
      <v-card>
        <v-card-title class="headline">Remover meta?</v-card-title>
        <v-card-text class="finance-text-muted">Esta ação não pode ser desfeita.</v-card-text>
        <v-card-actions>
          <v-btn text @click="deleteDlg.open = false">Cancelar</v-btn>
          <v-spacer />
          <v-btn color="error" depressed :loading="deleteDlg.loading" @click="confirmDelete">Remover</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import axios from 'axios'
import { formatDatePtBR, todayIsoLocal } from '../../format'

export default {
  name: 'GoalsPage',
  props: {
    apiBase: { type: String, required: true },
    categories: { type: Array, default: () => [] },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: false,
      goals: [],
      dialog: {
        open: false,
        saving: false,
        form: { title: '', target_amount: '', deadline: todayIsoLocal(), income_category_id: null },
      },
      deleteDlg: { open: false, loading: false, item: null },
    }
  },
  computed: {
    incomeCategories() {
      return (this.categories || []).filter((c) => c.type === 'income')
    },
    canSave() {
      const t = (this.dialog.form.title || '').trim()
      const amt = parseFloat(String(this.dialog.form.target_amount).replace(',', '.'))
      const d = (this.dialog.form.deadline || '').trim()
      return t.length > 0 && Number.isFinite(amt) && amt >= 0.01 && d.length >= 10
    },
  },
  watch: {
    refreshKey() {
      this.load()
    },
  },
  mounted() {
    this.load()
  },
  methods: {
    formatBRL(v) {
      return this.$formatCurrencyBRL(v)
    },
    formatDate(iso) {
      return formatDatePtBR(iso)
    },
    baseUrl() {
      return (this.apiBase || '').replace(/\/$/, '')
    },
    async load() {
      const base = this.baseUrl()
      if (!base) return
      this.loading = true
      try {
        const { data } = await axios.get(`${base}/goals`)
        this.goals = data.data || []
      } catch (e) {
        this.goals = []
        this.$emit('error', 'Não foi possível carregar metas.')
      } finally {
        this.loading = false
      }
    },
    openCreate() {
      this.dialog.form = {
        title: '',
        target_amount: '',
        deadline: todayIsoLocal(),
        income_category_id: null,
      }
      this.dialog.open = true
    },
    async saveCreate() {
      if (!this.canSave) return
      const base = this.baseUrl()
      this.dialog.saving = true
      try {
        const payload = {
          title: (this.dialog.form.title || '').trim(),
          target_amount: parseFloat(String(this.dialog.form.target_amount).replace(',', '.')),
          deadline: this.dialog.form.deadline,
          income_category_id: this.dialog.form.income_category_id || null,
        }
        await axios.post(`${base}/goals`, payload)
        this.dialog.open = false
        this.$emit('saved')
        await this.load()
      } catch (e) {
        const d = e.response && e.response.data
        const msg = (d && d.message) || 'Não foi possível criar a meta.'
        this.$emit('error', msg)
      } finally {
        this.dialog.saving = false
      }
    },
    askDelete(g) {
      this.deleteDlg.item = g
      this.deleteDlg.open = true
    },
    async confirmDelete() {
      if (!this.deleteDlg.item) return
      const base = this.baseUrl()
      this.deleteDlg.loading = true
      try {
        await axios.delete(`${base}/goals/${this.deleteDlg.item.id}`)
        this.deleteDlg.open = false
        this.$emit('saved')
        await this.load()
      } catch (e) {
        this.$emit('error', 'Não foi possível remover a meta.')
      } finally {
        this.deleteDlg.loading = false
        this.deleteDlg.item = null
      }
    },
  },
}
</script>

<style scoped>
.goals-page {
  max-width: 900px;
  margin-left: auto;
  margin-right: auto;
}
</style>
