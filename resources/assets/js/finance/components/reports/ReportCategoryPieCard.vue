<template>
  <v-card class="report-card rounded-xl" flat outlined>
    <v-card-text class="pb-2 pt-4 px-4">
      <div class="d-flex align-center flex-wrap">
        <v-icon color="secondary" class="mr-2">mdi-chart-pie</v-icon>
        <span class="subtitle-1 font-weight-bold">Despesas por categoria</span>
        <v-spacer />
        <span class="text-caption secondary--text">{{ monthLabel }}</span>
      </div>
    </v-card-text>
    <v-card-text v-if="loading" class="text-center py-10">
      <v-progress-circular indeterminate color="primary" size="36" />
    </v-card-text>
    <template v-else-if="hasData">
      <div class="chart-pie-wrap px-2 pb-2">
        <canvas ref="pieCanvas" />
      </div>
      <v-list v-if="legendRows.length" dense class="pie-legend transparent py-0 px-2 px-sm-3 pb-4" two-line>
        <v-list-item
          v-for="row in legendRows"
          :key="row.key"
          class="pie-legend__item px-3 rounded-lg mb-1"
        >
          <v-list-item-icon class="my-2 mr-3 align-self-center">
            <div class="legend-dot legend-dot--ring" :style="legendDotStyle(row)" />
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title class="body-2 text-truncate font-weight-medium">{{ row.name }}</v-list-item-title>
            <v-list-item-subtitle class="tabular-nums secondary--text">
              {{ formatBRL(row.value) }}
            </v-list-item-subtitle>
          </v-list-item-content>
          <v-list-item-action class="align-self-center ma-0">
            <v-chip x-small outlined color="secondary" class="pie-legend__chip tabular-nums">
              {{ row.pct }}%
            </v-chip>
          </v-list-item-action>
        </v-list-item>
      </v-list>
    </template>
    <v-card-text v-else class="finance-text-muted text-body-2 pb-8 text-center">
      Sem despesas por categoria neste mês.
    </v-card-text>
  </v-card>
</template>

<script>
import axios from 'axios'
import Chart from 'chart.js/auto'
import { formatCurrencyBRL } from '../../currency'

/** Despesas: paleta harmônica (contraste legível em claro/escuro) */
const PALETTE = [
  '#5c6bc0',
  '#7e57c2',
  '#26a69a',
  '#ff7043',
  '#42a5f5',
  '#ab47bc',
  '#66bb6a',
  '#ec407a',
  '#ffa726',
  '#78909c',
]

export default {
  name: 'ReportCategoryPieCard',
  props: {
    apiBase: { type: String, required: true },
    /** YYYY-MM */
    month: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: false,
      categories: [],
      apiMonth: '',
      chartInstance: null,
    }
  },
  computed: {
    monthLabel() {
      if (!this.apiMonth || !/^\d{4}-\d{2}$/.test(this.apiMonth)) return ''
      const [y, m] = this.apiMonth.split('-')
      return `${m}/${y}`
    },
    slices() {
      const rows = (this.categories || [])
        .map((r) => ({
          key: r.category_key,
          name: r.category_name || '—',
          value: Number(r.expense) || 0,
        }))
        .filter((r) => r.value > 0.001)
        .sort((a, b) => b.value - a.value)
      return rows
    },
    hasData() {
      return this.slices.length > 0
    },
    expenseTotal() {
      return this.slices.reduce((sum, s) => sum + s.value, 0)
    },
    legendRows() {
      const total = this.expenseTotal
      return this.slices.map((s, i) => ({
        ...s,
        color: PALETTE[i % PALETTE.length],
        pct: total > 0 ? ((s.value / total) * 100).toFixed(1) : '0.0',
      }))
    },
  },
  watch: {
    month() {
      this.load()
    },
    refreshKey() {
      this.load()
    },
    '$vuetify.theme.dark'() {
      if (this.hasData) {
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
    formatBRL(v) {
      return this.$formatCurrencyBRL(v)
    },
    legendDotStyle(row) {
      return {
        backgroundColor: row.color,
        boxShadow: `0 0 0 2px ${row.color}33`,
      }
    },
    destroyChart() {
      if (this.chartInstance) {
        this.chartInstance.destroy()
        this.chartInstance = null
      }
    },
    async load() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      const m = this.month
      if (!base || !m) return
      this.loading = true
      this.destroyChart()
      try {
        const { data } = await axios.get(`${base}/reports/categories`, { params: { month: m } })
        this.categories = data.categories || []
        this.apiMonth = data.month || m
      } catch (e) {
        this.categories = []
        this.$emit('error', 'Não foi possível carregar categorias.')
      } finally {
        this.loading = false
      }
      await this.$nextTick()
      this.renderChart()
    },
    renderChart() {
      const canvas = this.$refs.pieCanvas
      if (!canvas || !this.hasData) return
      this.destroyChart()
      const rows = this.legendRows
      const ctx = canvas.getContext('2d')
      const dark = this.$vuetify.theme.dark
      const border = dark ? '#2c2f36' : '#ffffff'
      const totalExpenses = this.expenseTotal

      const centerPlugin = {
        id: 'financePieCenter',
        afterDraw: (chart) => {
          const meta = chart.getDatasetMeta(0)
          if (!meta?.data?.[0]) return
          const { x, y } = meta.data[0]
          const c = chart.ctx
          c.save()
          c.textAlign = 'center'
          c.textBaseline = 'middle'
          c.fillStyle = dark ? 'rgba(255,255,255,0.5)' : 'rgba(44,47,54,0.55)'
          c.font = '600 11px system-ui, -apple-system, "Segoe UI", sans-serif'
          c.fillText('Total no mês', x, y - 14)
          c.fillStyle = dark ? '#ffffff' : '#1a1c21'
          c.font = '700 16px system-ui, -apple-system, "Segoe UI", sans-serif'
          c.fillText(formatCurrencyBRL(totalExpenses), x, y + 12)
          c.restore()
        },
      }

      this.chartInstance = new Chart(ctx, {
        type: 'doughnut',
        plugins: [centerPlugin],
        data: {
          labels: rows.map((r) => r.name),
          datasets: [
            {
              data: rows.map((r) => r.value),
              backgroundColor: rows.map((r) => r.color),
              borderWidth: 2,
              borderColor: border,
              hoverBorderWidth: 2,
              hoverBorderColor: border,
              spacing: 2,
              borderRadius: 6,
              hoverOffset: 10,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '62%',
          layout: {
            padding: { top: 6, bottom: 6, left: 4, right: 4 },
          },
          animation: {
            animateRotate: true,
            animateScale: true,
            duration: 750,
            easing: 'easeOutQuart',
          },
          interaction: {
            mode: 'index',
            intersect: true,
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: dark ? 'rgba(28,30,36,0.96)' : 'rgba(255,255,255,0.98)',
              titleColor: dark ? '#fff' : '#1a1c21',
              bodyColor: dark ? 'rgba(255,255,255,0.88)' : 'rgba(26,28,33,0.88)',
              borderColor: dark ? 'rgba(255,255,255,0.12)' : 'rgba(44,47,54,0.12)',
              borderWidth: 1,
              padding: 12,
              cornerRadius: 10,
              displayColors: true,
              boxPadding: 6,
              boxWidth: 12,
              boxHeight: 12,
              usePointStyle: true,
              callbacks: {
                title: (items) => (items[0] ? items[0].label : ''),
                label: (ctx) => {
                  const raw = ctx.raw
                  const total = ctx.dataset.data.reduce((a, b) => a + b, 0)
                  const pct = total > 0 ? ((raw / total) * 100).toFixed(1) : '0'
                  return ` ${formatCurrencyBRL(raw)}  ·  ${pct}% do total`
                },
              },
            },
          },
        },
      })
    },
  },
}
</script>

<style scoped>
.chart-pie-wrap {
  position: relative;
  height: 260px;
  max-width: 420px;
  margin: 0 auto;
  filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.06));
}
.theme--dark .chart-pie-wrap {
  filter: drop-shadow(0 10px 28px rgba(0, 0, 0, 0.35));
}
@media (min-width: 600px) {
  .chart-pie-wrap {
    height: 300px;
    max-width: 440px;
  }
}

.pie-legend__item {
  border: 1px solid rgba(0, 0, 0, 0.06);
  transition: background-color 0.2s ease, border-color 0.2s ease;
}
.theme--dark .pie-legend__item {
  border-color: rgba(255, 255, 255, 0.08);
}
.pie-legend__item:hover {
  background-color: rgba(0, 0, 0, 0.03) !important;
}
.theme--dark .pie-legend__item:hover {
  background-color: rgba(255, 255, 255, 0.05) !important;
}

.legend-dot {
  width: 14px;
  height: 14px;
  border-radius: 5px;
}
.legend-dot--ring {
  border: 2px solid rgba(255, 255, 255, 0.35);
}
.theme--dark .legend-dot--ring {
  border-color: rgba(0, 0, 0, 0.25);
}

.pie-legend__chip {
  font-weight: 600;
  letter-spacing: 0.02em;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
