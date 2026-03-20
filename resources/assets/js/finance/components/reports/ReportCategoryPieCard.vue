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
      <v-list v-if="legendRows.length" dense class="transparent py-0 px-3 pb-4" two-line>
        <v-list-item v-for="row in legendRows" :key="row.key" class="px-2 rounded-lg mb-1">
          <v-list-item-icon class="my-2 mr-3">
            <div class="legend-dot" :style="{ backgroundColor: row.color }" />
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title class="body-2 text-truncate">{{ row.name }}</v-list-item-title>
            <v-list-item-subtitle class="tabular-nums">{{ formatBRL(row.value) }}</v-list-item-subtitle>
          </v-list-item-content>
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

/** Despesas: tons variados; vermelho aparece mas não domina */
const PALETTE = [
  '#5c6bc0',
  '#7e57c2',
  '#ff7043',
  '#26a69a',
  '#ec407a',
  '#4caf50',
  '#ab47bc',
  '#ff5252',
  '#42a5f5',
  '#9575cd',
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
    legendRows() {
      return this.slices.map((s, i) => ({
        ...s,
        color: PALETTE[i % PALETTE.length],
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
        await this.$nextTick()
        this.renderChart()
      } catch (e) {
        this.categories = []
        this.$emit('error', 'Não foi possível carregar categorias.')
      } finally {
        this.loading = false
      }
    },
    renderChart() {
      const canvas = this.$refs.pieCanvas
      if (!canvas || !this.hasData) return
      this.destroyChart()
      const rows = this.legendRows
      const ctx = canvas.getContext('2d')
      const border = this.$vuetify.theme.dark ? '#353942' : '#ffffff'
      this.chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: rows.map((r) => r.name),
          datasets: [
            {
              data: rows.map((r) => r.value),
              backgroundColor: rows.map((r) => r.color),
              borderWidth: 2,
              borderColor: border,
              hoverOffset: 6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '58%',
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx) => {
                  const raw = ctx.raw
                  const total = ctx.dataset.data.reduce((a, b) => a + b, 0)
                  const pct = total > 0 ? ((raw / total) * 100).toFixed(1) : '0'
                  return `${ctx.label}: ${formatCurrencyBRL(raw)} (${pct}%)`
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
  height: 240px;
  max-width: 400px;
  margin: 0 auto;
}
@media (min-width: 600px) {
  .chart-pie-wrap {
    height: 280px;
  }
}

.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 4px;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
