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
        <div class="text-caption secondary--text mt-3">Carregando metas…</div>
      </v-card-text>
      <v-card-text v-else-if="!goals.length" class="finance-text-muted text-body-2 py-10 text-center">
        Nenhuma meta ainda. Crie uma para acompanhar objetivos financeiros.
      </v-card-text>
      <v-list v-else class="py-0 transparent" two-line>
        <template v-for="(g, i) in goals">
          <v-list-item
            :key="g.id"
            class="px-2 goal-list-item"
            :class="{
              'goal-near-deadline': g.is_deadline_near && !g.is_past_deadline,
              'goal-past-deadline': g.is_past_deadline,
            }"
          >
            <v-list-item-content>
              <div class="d-flex align-center flex-wrap">
                <v-list-item-title class="font-weight-medium">{{ g.title }}</v-list-item-title>
                <v-chip
                  v-if="g.is_deadline_near && !g.is_past_deadline && g.progress_percent < 100"
                  x-small
                  class="ml-2"
                  color="warning"
                  text-color="white"
                >
                  Prazo próximo
                </v-chip>
                <v-chip v-if="g.is_past_deadline" x-small class="ml-2" color="error" outlined>
                  Prazo vencido
                </v-chip>
              </div>
              <v-list-item-subtitle class="text-caption">
                Prazo {{ formatDate(g.deadline) }}
                <span v-if="g.income_category"> · {{ g.income_category.name }}</span>
              </v-list-item-subtitle>
              <div class="mt-2">
                <div class="d-flex justify-space-between text-caption mb-1">
                  <span>
                    <span :class="amountClass(g)">{{ formatBRL(g.current_amount) }}</span>
                    <span class="secondary--text"> / {{ formatBRL(g.target_amount) }}</span>
                  </span>
                  <span class="font-weight-medium" :class="progressTextClass(g)">{{ progressLabel(g) }}</span>
                </div>
                <v-progress-linear
                  :value="Math.min(100, g.progress_percent)"
                  :color="progressColor(g)"
                  height="10"
                  rounded
                />
              </div>
              <v-alert
                v-if="g.insight_summary"
                dense
                text
                type="info"
                class="mt-3 mb-0 py-2 goal-insight-alert"
                border="left"
                colored-border
              >
                <span class="text-caption text-body-2">{{ g.insight_summary }}</span>
              </v-alert>
              <v-alert
                v-if="g.context_note"
                dense
                text
                type="warning"
                class="mt-2 mb-0 py-2 goal-insight-alert"
                border="left"
                colored-border
              >
                <span class="text-caption text-body-2">{{ g.context_note }}</span>
              </v-alert>
            </v-list-item-content>
            <v-list-item-action class="flex-column align-end mx-0">
              <div class="d-flex flex-nowrap">
                <v-btn
                  v-if="g.income_category_id"
                  depressed
                  rounded
                  x-small
                  color="primary"
                  class="text-none mr-1"
                  :loading="syncLoading[g.id]"
                  @click="syncGoal(g)"
                >
                  <v-icon left x-small>mdi-sync</v-icon>
                  Sincronizar
                </v-btn>
                <v-btn icon small aria-label="Remover meta" @click="askDelete(g)">
                  <v-icon small color="secondary">mdi-delete-outline</v-icon>
                </v-btn>
              </div>
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
            :value="dialog.form.target_amount_str"
            label="Valor alvo"
            outlined
            dense
            placeholder="0,00"
            hint="Digite números; os dois últimos dígitos são centavos (ex.: 10000 → R$ 100,00)"
            persistent-hint
            hide-details="auto"
            inputmode="decimal"
            class="mb-3"
            @input="onTargetAmountInput"
            @blur="normalizeTargetAmount"
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
import { formatBRLDigitsAsTyping, parseBRLDigitsToNumber, parseCurrencyBRLInput } from '../../currency'
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
      syncLoading: {},
      dialog: {
        open: false,
        saving: false,
        form: {
          title: '',
          target_amount_str: '',
          deadline: todayIsoLocal(),
          income_category_id: null,
        },
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
      const amt = this.parsedTargetAmount()
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
    parsedTargetAmount() {
      const str = this.dialog.form.target_amount_str
      let n = parseBRLDigitsToNumber(str)
      if (Number.isNaN(n)) n = parseCurrencyBRLInput(str)
      return n
    },
    onTargetAmountInput(val) {
      this.dialog.form.target_amount_str = formatBRLDigitsAsTyping(val)
    },
    normalizeTargetAmount() {
      const n = this.parsedTargetAmount()
      if (!Number.isNaN(n) && n >= 0) {
        const cents = Math.round(n * 100)
        this.dialog.form.target_amount_str = formatBRLDigitsAsTyping(String(cents))
      }
    },
    progressColor(g) {
      if (g.is_past_deadline && g.progress_percent < 100) return 'error'
      if (g.is_deadline_near && g.progress_percent < 100) return 'warning'
      if (g.progress_percent >= 100) return 'success'
      return 'primary'
    },
    progressTextClass(g) {
      if (g.progress_percent >= 100) return 'success--text'
      if (g.is_past_deadline && g.progress_percent < 100) return 'error--text'
      return ''
    },
    progressLabel(g) {
      return `${g.progress_percent}%`
    },
    amountClass(g) {
      if (g.progress_percent >= 100) return 'success--text font-weight-medium'
      return 'font-weight-medium'
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
        target_amount_str: '',
        deadline: todayIsoLocal(),
        income_category_id: null,
      }
      this.dialog.open = true
    },
    async saveCreate() {
      if (!this.canSave) return
      const base = this.baseUrl()
      const amt = this.parsedTargetAmount()
      this.dialog.saving = true
      try {
        const payload = {
          title: (this.dialog.form.title || '').trim(),
          target_amount: amt,
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
    async syncGoal(g) {
      const base = this.baseUrl()
      if (!base || !g.id) return
      this.$set(this.syncLoading, g.id, true)
      try {
        await axios.post(`${base}/goals/${g.id}/sync`)
        this.$emit('saved')
        await this.load()
      } catch (e) {
        const d = e.response && e.response.data
        const msg = (d && d.message) || 'Não foi possível sincronizar com as receitas.'
        this.$emit('error', msg)
      } finally {
        this.$set(this.syncLoading, g.id, false)
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
.goal-list-item.goal-near-deadline {
  border-left: 3px solid #f9a825;
  padding-left: 10px !important;
  margin-left: 4px;
  border-radius: 4px;
}
.goal-list-item.goal-past-deadline {
  border-left: 3px solid #e53935;
  padding-left: 10px !important;
  margin-left: 4px;
  border-radius: 4px;
}
.goal-insight-alert {
  border-radius: 8px !important;
}
</style>
