<template>
  <v-card class="report-card rounded-xl" flat outlined>
    <v-card-text class="pb-2 pt-4 px-4">
      <div class="d-flex align-center flex-wrap gap-y-2">
        <v-icon color="secondary" class="mr-2">mdi-chart-bar</v-icon>
        <span class="subtitle-1 font-weight-bold">Fluxo mensal</span>
        <v-spacer />
        <v-select
          v-model="localMonths"
          :items="monthOptions"
          item-text="text"
          item-value="value"
          dense
          outlined
          hide-details
          class="report-card__select"
          label="Período"
          @change="reload"
        />
      </div>
    </v-card-text>
    <v-card-text v-if="loading" class="text-center py-10">
      <v-progress-circular indeterminate color="primary" size="36" />
    </v-card-text>
    <template v-else-if="series.length">
      <div class="chart-bar-wrap px-2 pb-2">
        <canvas ref="barCanvas" />
      </div>
      <p class="text-caption secondary--text text-center px-4 pb-4 mb-0">
        Barras: receitas e despesas por mês · Linha: saldo acumulado (caixa), mês a mês
      </p>
    </template>
    <v-card-text v-else class="finance-text-muted text-body-2 pb-8 text-center">
      Sem dados para o período.
    </v-card-text>
  </v-card>
</template>

<script>
import axios from 'axios'
import Chart from 'chart.js/auto'
import { formatCurrencyBRLAxis } from '../../currency'

export default {
  name: 'ReportMonthlyBarCard',
  props: {
    apiBase: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: false,
      series: [],
      localMonths: 6,
      monthOptions: [
        { text: '6 meses', value: 6 },
        { text: '12 meses', value: 12 },
      ],
      chartInstance: null,
    }
  },
  watch: {
    refreshKey() {
      this.reload()
    },
    '$vuetify.theme.dark'() {
      if (this.series.length) {
        this.$nextTick(() => this.renderChart())
      }
    },
  },
  mounted() {
    this.reload()
  },
  beforeDestroy() {
    this.destroyChart()
  },
  methods: {
    destroyChart() {
      if (this.chartInstance) {
        this.chartInstance.destroy()
        this.chartInstance = null
      }
    },
    formatAxis(v) {
      return formatCurrencyBRLAxis(v)
    },
    labelMes(iso) {
      if (!iso || !/^\d{4}-\d{2}$/.test(iso)) return iso
      const [y, m] = iso.split('-')
      return `${m}/${y.slice(2)}`
    },
    chartThemeColors() {
      const d = this.$vuetify.theme.dark
      return {
        legend: d ? 'rgba(255,255,255,0.75)' : 'rgba(44,47,54,0.72)',
        tickX: d ? 'rgba(255,255,255,0.6)' : 'rgba(44,47,54,0.62)',
        gridY: d ? 'rgba(255,255,255,0.08)' : 'rgba(44,47,54,0.1)',
        tickY: d ? 'rgba(255,255,255,0.55)' : 'rgba(44,47,54,0.55)',
      }
    },
    async reload() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) return
      this.loading = true
      this.destroyChart()
      try {
        const n = Math.max(6, Math.min(12, Number(this.localMonths) || 6))
        const { data } = await axios.get(`${base}/reports/trend`, { params: { months: n } })
        this.series = data.series || []
      } catch (e) {
        this.series = []
        this.$emit('error', 'Não foi possível carregar a série mensal.')
      } finally {
        this.loading = false
      }
      await this.$nextTick()
      this.renderChart()
    },
    renderChart() {
      const canvas = this.$refs.barCanvas
      if (!canvas || !this.series.length) return
      this.destroyChart()
      const labels = this.series.map((r) => this.labelMes(r.month))
      const receitas = this.series.map((r) => Number(r.receitas) || 0)
      const despesas = this.series.map((r) => (Number(r.despesas_caixa) || 0) + (Number(r.despesas_cartao) || 0))
      const saldoAcum = this.series.map((r) => Number(r.saldo_acumulado) || 0)
      const isNarrow = typeof window !== 'undefined' && window.innerWidth < 600
      const tc = this.chartThemeColors()
      const ctx = canvas.getContext('2d')
      this.chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            {
              type: 'bar',
              label: 'Receitas',
              data: receitas,
              yAxisID: 'y',
              backgroundColor: 'rgba(76, 175, 80, 0.9)',
              borderRadius: isNarrow ? 5 : 8,
              maxBarThickness: isNarrow ? 28 : 40,
            },
            {
              type: 'bar',
              label: 'Despesas',
              data: despesas,
              yAxisID: 'y',
              backgroundColor: 'rgba(255, 0, 0, 0.85)',
              borderRadius: isNarrow ? 5 : 8,
              maxBarThickness: isNarrow ? 28 : 40,
            },
            {
              type: 'line',
              label: 'Saldo acumulado (caixa)',
              data: saldoAcum,
              yAxisID: 'y1',
              borderColor: 'rgba(33, 150, 243, 0.95)',
              backgroundColor: 'rgba(33, 150, 243, 0.12)',
              borderWidth: 2,
              tension: 0.25,
              pointRadius: isNarrow ? 2 : 3,
              fill: false,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                padding: 16,
                font: { size: 11 },
                color: tc.legend,
              },
            },
            tooltip: {
              callbacks: {
                label: (c) => {
                  const v = c.parsed.y
                  return `${c.dataset.label}: ${this.$formatCurrencyBRL(v)}`
                },
              },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: {
                maxRotation: 45,
                minRotation: 0,
                font: { size: isNarrow ? 9 : 11 },
                color: tc.tickX,
              },
            },
            y: {
              position: 'left',
              beginAtZero: true,
              grid: { color: tc.gridY },
              ticks: {
                color: tc.tickY,
                callback: (value) => this.formatAxis(value),
                maxTicksLimit: isNarrow ? 5 : 8,
              },
            },
            y1: {
              position: 'right',
              grid: { drawOnChartArea: false },
              ticks: {
                color: tc.tickY,
                callback: (value) => this.formatAxis(value),
                maxTicksLimit: isNarrow ? 5 : 8,
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
.report-card__select {
  max-width: 140px;
  margin-top: 0;
  margin-bottom: 0;
}

.chart-bar-wrap {
  position: relative;
  height: 260px;
}
@media (min-width: 600px) {
  .chart-bar-wrap {
    height: 300px;
  }
}

.gap-y-2 > * {
  margin-bottom: 4px;
}
</style>
