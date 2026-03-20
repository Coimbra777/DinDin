<template>
  <div class="planning-page px-1">
    <p class="text-caption secondary--text mb-4">
      Defina quanto planeja gastar e economizar por mês. Os valores realizados vêm das suas transações.
    </p>

    <v-card class="rounded-xl" flat outlined>
      <v-card-title class="subtitle-1 font-weight-bold py-4">
        <v-icon left color="primary">mdi-calendar-check</v-icon>
        Planejamento mensal
        <v-spacer />
        <v-btn color="primary" depressed rounded @click="openCreate">
          <v-icon left small>mdi-plus</v-icon>
          Novo
        </v-btn>
        <v-btn icon class="ml-1" :loading="loading" aria-label="Atualizar" @click="load">
          <v-icon>mdi-refresh</v-icon>
        </v-btn>
      </v-card-title>
      <v-divider />

      <v-card-text v-if="loading && !items.length" class="text-center py-8">
        <v-progress-circular indeterminate color="primary" />
      </v-card-text>

      <v-data-table
        v-else
        :headers="headers"
        :items="items"
        :loading="loading"
        dense
        class="transparent"
        mobile-breakpoint="0"
      >
        <template #item.year_month="{ item }">
          {{ formatMonthLabel(item.year_month) }}
        </template>
        <template #item.planned_expense="{ item }">
          <span class="tabular-nums">{{ formatBRL(item.planned_expense) }}</span>
        </template>
        <template #item.planned_saving="{ item }">
          <span class="tabular-nums">{{ formatBRL(item.planned_saving) }}</span>
        </template>
        <template #item.actual_total_expense="{ item }">
          <span class="tabular-nums">{{ formatBRL(item.actual_total_expense) }}</span>
        </template>
        <template #item.delta_expense_vs_plan="{ item }">
          <span class="tabular-nums" :class="deltaClass(item.delta_expense_vs_plan)">
            {{ formatDelta(item.delta_expense_vs_plan) }}
          </span>
        </template>
        <template #item.actions="{ item }">
          <v-btn icon small aria-label="Editar" @click="openEdit(item)">
            <v-icon small color="secondary">mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon small aria-label="Excluir" @click="askDelete(item)">
            <v-icon small color="error">mdi-delete-outline</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog.open" max-width="480" persistent>
      <v-card class="rounded-lg">
        <v-card-title class="headline">
          {{ dialog.id ? 'Editar plano' : 'Novo plano' }}
        </v-card-title>
        <v-card-text class="pt-4">
          <v-select
            v-model="dialog.form.year_month"
            :items="monthItems"
            item-text="text"
            item-value="value"
            label="Mês"
            outlined
            dense
            hide-details="auto"
            prepend-inner-icon="mdi-calendar-month"
            :disabled="!!dialog.id"
            class="mb-3"
          />
          <v-text-field
            v-model.number="dialog.form.planned_expense"
            label="Meta de despesas (R$)"
            type="number"
            min="0"
            step="0.01"
            outlined
            dense
            hide-details="auto"
            class="mb-3"
          />
          <v-text-field
            v-model.number="dialog.form.planned_saving"
            label="Meta de economia (R$)"
            type="number"
            min="0"
            step="0.01"
            outlined
            dense
            hide-details="auto"
          />
        </v-card-text>
        <v-card-actions>
          <v-btn text @click="dialog.open = false">Cancelar</v-btn>
          <v-spacer />
          <v-btn color="primary" depressed :loading="dialog.saving" @click="save">Guardar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDlg.open" max-width="400" persistent>
      <v-card class="rounded-lg">
        <v-card-title class="headline">Excluir plano?</v-card-title>
        <v-card-text class="finance-text-muted">
          Mês: {{ deleteDlg.item ? formatMonthLabel(deleteDlg.item.year_month) : '' }}
        </v-card-text>
        <v-card-actions>
          <v-btn text @click="deleteDlg.open = false">Cancelar</v-btn>
          <v-spacer />
          <v-btn color="error" depressed :loading="deleteDlg.loading" @click="confirmDelete">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import axios from 'axios'
import { monthChoices } from '../../format'

export default {
  name: 'PlanningPage',
  props: {
    apiBase: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: false,
      items: [],
      monthItems: monthChoices(36),
      headers: [
        { text: 'Mês', value: 'year_month', sortable: true },
        { text: 'Meta despesas', value: 'planned_expense', align: 'end' },
        { text: 'Meta economia', value: 'planned_saving', align: 'end' },
        { text: 'Despesa real', value: 'actual_total_expense', align: 'end' },
        { text: 'Δ despesa', value: 'delta_expense_vs_plan', align: 'end' },
        { text: '', value: 'actions', sortable: false, align: 'end', width: 100 },
      ],
      dialog: {
        open: false,
        id: null,
        saving: false,
        form: { year_month: '', planned_expense: 0, planned_saving: 0 },
      },
      deleteDlg: { open: false, loading: false, item: null },
    }
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
    formatMonthLabel(ym) {
      if (!ym) return '—'
      const [y, m] = String(ym).split('-')
      return m && y ? `${m}/${y}` : ym
    },
    formatDelta(v) {
      const n = Number(v)
      if (n > 0) return '+' + this.formatBRL(n)
      return this.formatBRL(n)
    },
    deltaClass(v) {
      const n = Number(v)
      if (n > 0) return 'finance-amount-expense'
      if (n < 0) return 'finance-amount-income'
      return ''
    },
    async load() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) return
      this.loading = true
      try {
        const { data } = await axios.get(`${base}/planning`)
        this.items = data.data || []
      } catch (e) {
        this.items = []
        this.$emit('error', 'Não foi possível carregar o planejamento.')
      } finally {
        this.loading = false
      }
    },
    openCreate() {
      const now = new Date()
      const ym = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
      this.dialog = {
        open: true,
        id: null,
        saving: false,
        form: { year_month: ym, planned_expense: 0, planned_saving: 0 },
      }
    },
    openEdit(item) {
      this.dialog = {
        open: true,
        id: item.id,
        saving: false,
        form: {
          year_month: item.year_month,
          planned_expense: Number(item.planned_expense),
          planned_saving: Number(item.planned_saving),
        },
      }
    },
    async save() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) return
      const { year_month, planned_expense, planned_saving } = this.dialog.form
      if (!year_month) {
        this.$emit('error', 'Escolha o mês.')
        return
      }
      this.dialog.saving = true
      try {
        if (this.dialog.id) {
          await axios.put(`${base}/planning/${this.dialog.id}`, {
            planned_expense,
            planned_saving,
          })
        } else {
          await axios.post(`${base}/planning`, {
            year_month,
            planned_expense,
            planned_saving,
          })
        }
        this.dialog.open = false
        await this.load()
      } catch (e) {
        const msg =
          e.response &&
          e.response.data &&
          (e.response.data.message || (e.response.data.errors && JSON.stringify(e.response.data.errors)))
        this.$emit('error', typeof msg === 'string' ? msg : 'Não foi possível guardar.')
      } finally {
        this.dialog.saving = false
      }
    },
    askDelete(item) {
      this.deleteDlg = { open: true, loading: false, item }
    },
    async confirmDelete() {
      if (!this.deleteDlg.item) return
      const base = (this.apiBase || '').replace(/\/$/, '')
      this.deleteDlg.loading = true
      try {
        await axios.delete(`${base}/planning/${this.deleteDlg.item.id}`)
        this.deleteDlg.open = false
        await this.load()
      } catch (e) {
        this.$emit('error', 'Não foi possível excluir.')
      } finally {
        this.deleteDlg.loading = false
        this.deleteDlg.item = null
      }
    },
  },
}
</script>

<style scoped>
.planning-page {
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}
.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
