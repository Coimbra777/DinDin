<template>
  <div class="projection-root">
    <v-card class="proj-card rounded-xl mb-4" flat outlined>
      <v-card-text class="pb-2 pt-5 px-4 px-sm-5">
        <div class="d-flex align-center flex-wrap mb-2">
          <v-icon color="secondary" class="mr-2">mdi-chart-timeline-variant</v-icon>
          <span class="text-h6 font-weight-bold">Projeção financeira</span>
          <v-chip x-small outlined class="ml-2" color="secondary">12 meses</v-chip>
          <v-spacer />
          <v-tooltip bottom max-width="320">
            <template #activator="{ on, attrs }">
              <v-btn icon small v-bind="attrs" aria-label="Como calculamos" v-on="on">
                <v-icon small color="secondary">mdi-information-outline</v-icon>
              </v-btn>
            </template>
            <span>
              Cada mês mostra só os <strong>lançamentos reais</strong> já guardados nesse mês (receitas e despesas).
              O <strong>saldo</strong> é receitas − despesas, acumulado a partir do teu saldo até ao fim do
              <strong>mês atual</strong> — alinhado ao dashboard. Meses sem movimento aparecem a zero.
            </span>
          </v-tooltip>
        </div>

        <v-alert
          v-if="!loading && displayRows.length"
          dense
          text
          type="info"
          border="left"
          colored-border
          class="proj-ref-alert mb-0 text-body-2"
        >
          <span class="text-caption secondary--text">
            Saldo até fim do mês atual (base da projeção): {{ formatBRL(openingCash) }}
          </span>
        </v-alert>
      </v-card-text>
    </v-card>

    <v-card class="proj-card rounded-xl mb-4" flat outlined>
      <v-card-text class="py-4 px-3 px-sm-4">
        <div class="d-flex align-center mb-2">
          <v-icon color="secondary" class="mr-2" small>mdi-chart-areaspline</v-icon>
          <span class="subtitle-1 font-weight-bold">Saldo ao fim de cada mês</span>
        </div>
        <p class="text-caption secondary--text mb-3">
          Evolução do saldo acumulado após os lançamentos já registados em cada mês futuro.
        </p>
        <div v-if="loading" class="text-center py-8">
          <v-progress-circular indeterminate color="primary" size="40" />
        </div>
        <div v-else-if="displayRows.length" class="proj-chart-wrap">
          <canvas ref="lineCanvas" />
        </div>
        <p v-else class="finance-text-muted text-body-2 py-6 text-center mb-0">Sem dados para o gráfico.</p>
      </v-card-text>
    </v-card>

    <v-card class="proj-card rounded-xl" flat outlined>
      <v-card-text class="py-4 px-2 px-sm-3">
        <div class="d-flex align-center px-2 mb-3">
          <v-icon color="secondary" class="mr-2" small>mdi-table-large</v-icon>
          <span class="subtitle-1 font-weight-bold">Detalhe mensal</span>
        </div>

        <template v-if="loading">
          <v-row dense class="px-2 pb-2">
            <v-col v-for="n in 6" :key="n" cols="12" sm="6" md="4">
              <v-skeleton-loader type="card" height="120" class="rounded-xl" boilerplate />
            </v-col>
          </v-row>
        </template>

        <v-simple-table v-else-if="displayRows.length" dense class="proj-table transparent">
          <thead>
            <tr>
              <th>Mês</th>
              <th class="text-right">Receitas</th>
              <th class="text-right d-none d-sm-table-cell">Despesas</th>
              <th class="text-right">Saldo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in displayRows" :key="r.month">
              <td class="font-weight-medium">{{ monthLabel(r.month) }}</td>
              <td class="text-right tabular-nums finance-amount-income">{{ formatBRL(r.income) }}</td>
              <td class="text-right tabular-nums finance-amount-expense d-none d-sm-table-cell">
                {{ formatBRL(r.expense) }}
              </td>
              <td class="text-right tabular-nums font-weight-medium" :class="saldoClass(r.balance)">
                {{ formatSaldoCell(r.balance) }}
              </td>
            </tr>
          </tbody>
        </v-simple-table>

        <p v-else class="finance-text-muted text-body-2 pb-6 px-2 mb-0 text-center">
          Não foi possível carregar a projeção.
        </p>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import axios from 'axios'
import Chart from 'chart.js/auto'
import { formatCurrencyBRLAxis } from '../currency'

const PT_MONTHS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']

function monthLabelFromYm(ym) {
  if (!ym || !/^\d{4}-\d{2}$/.test(String(ym))) return '—'
  const [, m] = String(ym).split('-')
  const mi = parseInt(m, 10) - 1
  const y = String(ym).slice(0, 4)
  if (mi < 0 || mi > 11) return ym
  return `${PT_MONTHS[mi]}/${y}`
}

export default {
  name: 'ProjectionCard',
  props: {
    apiBase: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: true,
      rows: [],
      chartInstance: null,
    }
  },
  computed: {
    displayRows() {
      return this.rows || []
    },
    /** Saldo ao fim do mês atual (inferido do 1.º mês projetado). */
    openingCash() {
      const rows = this.displayRows
      if (!rows.length) return 0
      const m = rows[0]
      const bal = Number(m.balance) || 0
      const inc = Number(m.income) || 0
      const exp = Number(m.expense) || 0
      return bal - (inc - exp)
    },
  },
  watch: {
    refreshKey() {
      this.load()
    },
    '$vuetify.theme.dark'() {
      if (!this.loading && this.displayRows.length) {
        this.$nextTick(() => this.renderChart())
      }
    },
  },
  mounted() {
    this.load()
  },
  beforeDestroy() {
    this.destroyChart()
  },
  methods: {
    monthLabel: monthLabelFromYm,
    formatBRL(v) {
      return this.$formatCurrencyBRL(v)
    },
    formatSaldoCell(saldo) {
      const n = Number(saldo) || 0
      const abs = this.formatBRL(Math.abs(n))
      if (n > 0) return `+ ${abs}`
      if (n < 0) return `− ${abs}`
      return this.formatBRL(0)
    },
    saldoClass(saldo) {
      const n = Number(saldo)
      if (n > 0) return 'finance-amount-income'
      if (n < 0) return 'finance-amount-expense'
      return 'secondary--text'
    },
    destroyChart() {
      if (this.chartInstance) {
        this.chartInstance.destroy()
        this.chartInstance = null
      }
    },
    chartColors() {
      const d = this.$vuetify.theme.dark
      return {
        fill: d ? 'rgba(76, 175, 80, 0.25)' : 'rgba(76, 175, 80, 0.18)',
        border: d ? 'rgba(129, 199, 132, 0.95)' : '#43a047',
        grid: d ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)',
        ticks: d ? 'rgba(255,255,255,0.65)' : 'rgba(0,0,0,0.55)',
      }
    },
    renderChart() {
      const canvas = this.$refs.lineCanvas
      if (!canvas || !this.displayRows.length) return
      this.destroyChart()
      const ctx = canvas.getContext('2d')
      const c = this.chartColors()
      const labels = this.displayRows.map((r) => monthLabelFromYm(r.month))
      const balances = this.displayRows.map((r) => Number(r.balance) || 0)

      this.chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [
            {
              label: 'Saldo',
              data: balances,
              fill: true,
              backgroundColor: c.fill,
              borderColor: c.border,
              borderWidth: 2,
              tension: 0.35,
              pointRadius: 3,
              pointHoverRadius: 5,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (item) => ` ${this.$formatCurrencyBRL(item.parsed.y)}`,
              },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { maxRotation: 45, color: c.ticks, font: { size: 10 } },
            },
            y: {
              beginAtZero: false,
              grid: { color: c.grid },
              ticks: {
                color: c.ticks,
                maxTicksLimit: 6,
                callback: (value) => formatCurrencyBRLAxis(value),
              },
            },
          },
        },
      })
    },
    async load() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) {
        this.loading = false
        return
      }
      this.loading = true
      this.destroyChart()
      try {
        const { data } = await axios.get(`${base}/projection`)
        this.rows = Array.isArray(data.months) ? data.months : []
        this.$emit('loaded', data)
      } catch (e) {
        this.rows = []
        this.$emit('error', 'Não foi possível carregar a projeção.')
      } finally {
        this.loading = false
      }
      await this.$nextTick()
      if (this.displayRows.length) this.renderChart()
    },
  },
}
</script>

<style scoped>
.projection-root {
  max-width: 1100px;
  margin-left: auto;
  margin-right: auto;
}

.proj-ref-alert {
  border-radius: 12px !important;
}

.proj-chart-wrap {
  position: relative;
  height: 280px;
}
@media (min-width: 600px) {
  .proj-chart-wrap {
    height: 320px;
  }
}

.proj-table >>> thead th {
  font-size: 0.75rem !important;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.85;
}

.proj-table >>> tbody td {
  font-size: 0.875rem;
}

.finance-amount-income {
  color: #4caf50 !important;
}

.finance-amount-expense {
  color: #ff0000 !important;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
