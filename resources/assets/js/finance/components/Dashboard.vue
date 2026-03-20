<template>
  <div class="finance-dashboard">
    <!-- Skeleton -->
    <template v-if="loading && !hasPayload">
      <v-skeleton-loader type="card" class="mb-4 rounded-xl" boilerplate />
      <v-row dense>
        <v-col cols="6"><v-skeleton-loader type="image" height="100" class="rounded-xl" boilerplate /></v-col>
        <v-col cols="6"><v-skeleton-loader type="image" height="100" class="rounded-xl" boilerplate /></v-col>
      </v-row>
      <v-skeleton-loader type="image" height="200" class="mt-4 rounded-xl" boilerplate />
      <v-skeleton-loader type="list-item@4" class="mt-4 rounded-xl" boilerplate />
    </template>

    <template v-else>
      <v-overlay :value="reloading" absolute opacity="0.06" z-index="3" />

      <!-- 1. Saldo atual — grande, cor neutra -->
      <v-row class="mb-3 mb-sm-5">
        <v-col cols="12" class="px-2 px-sm-4">
          <v-card
            class="hero-balance rounded-xl"
            :class="{ 'hero-balance--elevated': $vuetify.breakpoint.smAndUp }"
            flat
            :elevation="$vuetify.breakpoint.smAndUp ? 1 : 0"
            outlined
          >
            <v-card-text class="text-center py-6 py-md-8 px-4">
              <div class="hero-balance__kicker text-caption font-weight-medium text-uppercase letter-wider secondary--text">
                Saldo atual
              </div>
              <div
                class="hero-balance__figure font-weight-bold tabular-nums"
                :class="heroBalanceTextClass"
              >
                {{ formatBRL(saldoReal) }}
              </div>
              <div class="text-caption secondary--text mt-1">
                {{ monthLabel }}
                <span class="mx-1">·</span>
                {{ totalTransacoes }} lançamento(s)
                <span class="d-none d-sm-inline"> · caixa (sem fatura)</span>
              </div>
              <v-chip
                v-if="mostrarMiniCartao"
                small
                outlined
                class="mt-3"
                color="secondary"
              >
                <v-icon left x-small>mdi-credit-card-outline</v-icon>
                Com cartão: {{ formatBRL(saldoComCartao) }}
              </v-chip>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- 2. Receitas | Despesas — grid mobile-first -->
      <v-row dense class="mb-4 mb-sm-5">
        <v-col cols="12" sm="6" class="px-2 px-sm-4">
          <v-card class="stat-tile stat-tile--income rounded-xl h-100" flat outlined>
            <v-card-text class="py-4 px-4 d-flex align-center">
              <div class="stat-tile__icon stat-tile__icon--income mr-4">
                <v-icon color="white" size="26">mdi-trending-up</v-icon>
              </div>
              <div class="min-w-0">
                <div class="text-overline font-weight-medium finance-label-income mb-1">
                  Receitas
                </div>
                <div class="stat-tile__value finance-amount-income font-weight-bold tabular-nums">
                  {{ formatBRL(receitasMes) }}
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" class="px-2 px-sm-4">
          <v-card class="stat-tile stat-tile--expense rounded-xl h-100" flat outlined>
            <v-card-text class="py-4 px-4 d-flex align-center">
              <div class="stat-tile__icon stat-tile__icon--expense mr-4">
                <v-icon color="white" size="26">mdi-trending-down</v-icon>
              </div>
              <div class="min-w-0">
                <div class="text-overline font-weight-medium finance-label-expense mb-1">
                  Despesas
                </div>
                <div class="stat-tile__value finance-amount-expense font-weight-bold tabular-nums">
                  {{ formatBRL(despesasTotalMes) }}
                </div>
                <div v-if="despesasCartaoMes > 0" class="text-caption secondary--text mt-1">
                  À vista {{ formatBRL(despesasCaixaMes) }}
                  <span class="mx-1">·</span>
                  Cartão {{ formatBRL(despesasCartaoMes) }}
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- 3. Gráfico -->
      <v-row class="mb-4 mb-sm-5">
        <v-col cols="12" class="px-2 px-sm-4">
          <v-card class="rounded-xl" flat outlined>
            <v-card-text class="pb-2 pt-4 px-4">
              <div class="d-flex align-center flex-wrap">
                <v-icon color="secondary" class="mr-2">mdi-chart-bar</v-icon>
                <span class="subtitle-1 font-weight-bold">Fluxo no mês</span>
                <v-spacer />
                <span class="text-caption secondary--text">{{ monthLabel }} · R$</span>
              </div>
            </v-card-text>
            <v-card-text class="pt-0 pb-4 px-2 px-sm-4">
              <div class="chart-wrap">
                <canvas ref="chartCanvas" />
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- 4. Últimas transações -->
      <v-row>
        <v-col cols="12" class="px-2 px-sm-4 pb-2">
          <transaction-list
            title="Últimas transações"
            :subtitle="monthLabel"
            :items="ultimasTransacoes"
            :loading="false"
            :show-actions="false"
            layout="comfortable"
          />
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script>
import axios from 'axios'
import Chart from 'chart.js/auto'
import { formatCurrencyBRLAxis } from '../currency'
import TransactionList from './TransactionList.vue'

export default {
  name: 'Dashboard',
  components: { TransactionList },
  props: {
    month: { type: String, required: true },
    apiBase: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: true,
      reloading: false,
      hasPayload: false,
      saldoReal: 0,
      saldoComCartao: 0,
      receitasMes: 0,
      despesasCaixaMes: 0,
      despesasCartaoMes: 0,
      totalTransacoes: 0,
      ultimasTransacoes: [],
      apiMonth: '',
      chartInstance: null,
    }
  },
  computed: {
    monthLabel() {
      if (!this.apiMonth || !/^\d{4}-\d{2}$/.test(this.apiMonth)) return '—'
      const [y, m] = this.apiMonth.split('-')
      return `${m}/${y}`
    },
    despesasTotalMes() {
      return this.despesasCaixaMes + this.despesasCartaoMes
    },
    mostrarMiniCartao() {
      return Math.abs(this.saldoComCartao - this.saldoReal) > 0.005 || this.despesasCartaoMes > 0
    },
    /** Saldo em tipografia neutra; só intensidade muda levemente */
    heroBalanceTextClass() {
      if (this.saldoReal > 0) return 'hero-balance__figure--positive'
      if (this.saldoReal < 0) return 'hero-balance__figure--negative'
      return ''
    },
  },
  watch: {
    month() {
      this.load(true)
    },
    refreshKey() {
      this.load(true)
    },
    '$vuetify.theme.dark'() {
      if (this.hasPayload) {
        this.$nextTick(() => this.renderChart())
      }
    },
  },
  mounted() {
    this.load(false)
  },
  beforeDestroy() {
    this.destroyChart()
  },
  methods: {
    formatBRL(v) {
      return this.$formatCurrencyBRL(v)
    },
    formatAxis(v) {
      return formatCurrencyBRLAxis(v)
    },
    destroyChart() {
      if (this.chartInstance) {
        this.chartInstance.destroy()
        this.chartInstance = null
      }
    },
    chartScaleColors() {
      const d = this.$vuetify.theme.dark
      return {
        tickX: d ? 'rgba(255,255,255,0.65)' : 'rgba(44,47,54,0.65)',
        gridY: d ? 'rgba(255,255,255,0.08)' : 'rgba(44,47,54,0.1)',
        tickY: d ? 'rgba(255,255,255,0.55)' : 'rgba(44,47,54,0.55)',
      }
    },
    async load(isRefresh) {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) {
        this.loading = false
        return
      }
      if (isRefresh) this.reloading = true
      else this.loading = true

      try {
        const { data } = await axios.get(`${base}/dashboard`, {
          params: { month: this.month },
        })
        const saldoR = Number(data.saldo_real)
        this.saldoReal = Number.isFinite(saldoR) ? saldoR : Number(data.saldo_atual) || 0
        this.saldoComCartao = Number(data.saldo_com_cartao) || 0
        this.receitasMes = Number(data.receitas_mes) || 0
        const dCaixa = data.despesas_caixa_mes
        this.despesasCaixaMes = Number(dCaixa !== undefined ? dCaixa : data.despesas_mes) || 0
        this.despesasCartaoMes = Number(data.despesas_cartao_mes) || 0
        this.totalTransacoes = Number(data.total_transacoes) || 0
        this.ultimasTransacoes = data.ultimas_transacoes || []
        this.apiMonth = data.month || this.month
        this.hasPayload = true

        await this.$nextTick()
        this.renderChart()
      } catch (e) {
        this.hasPayload = true
        this.$emit('error', 'Não foi possível carregar o dashboard.')
        this.destroyChart()
      } finally {
        this.loading = false
        this.reloading = false
      }
    },
    renderChart() {
      const canvas = this.$refs.chartCanvas
      if (!canvas) return

      this.destroyChart()
      const ctx = canvas.getContext('2d')
      const rec = this.receitasMes
      const desCaixa = this.despesasCaixaMes
      const desCartao = this.despesasCartaoMes
      const isNarrow = typeof window !== 'undefined' && window.innerWidth < 600
      const sc = this.chartScaleColors()

      this.chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Receitas', 'À vista', 'Cartão'],
          datasets: [
            {
              label: 'R$',
              data: [rec, desCaixa, desCartao],
              backgroundColor: [
                'rgba(76, 175, 80, 0.92)',
                'rgba(255, 0, 0, 0.85)',
                'rgba(103, 58, 183, 0.82)',
              ],
              borderColor: ['#4CAF50', '#ff0000', '#7E57C2'],
              borderWidth: 0,
              borderRadius: isNarrow ? 6 : 10,
              maxBarThickness: isNarrow ? 44 : 56,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (c) => this.formatBRL(c.parsed.y),
              },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: {
                maxRotation: 0,
                font: { size: isNarrow ? 10 : 11 },
                color: sc.tickX,
              },
            },
            y: {
              beginAtZero: true,
              grid: { color: sc.gridY },
              ticks: {
                maxTicksLimit: isNarrow ? 5 : 7,
                color: sc.tickY,
                callback: (value) => this.formatAxis(value),
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
.finance-dashboard {
  position: relative;
  max-width: 720px;
  margin-left: auto;
  margin-right: auto;
}
@media (min-width: 960px) {
  .finance-dashboard {
    max-width: 840px;
  }
}

.hero-balance {
  border-color: rgba(255, 255, 255, 0.1) !important;
}
.hero-balance--elevated {
  border-color: rgba(255, 255, 255, 0.12) !important;
}

.letter-wider {
  letter-spacing: 0.06em;
}

.hero-balance__figure {
  font-size: clamp(2rem, 8vw, 3.25rem);
  line-height: 1.15;
  margin-top: 0.25rem;
}

/* Saldo: positivo verde, negativo vermelho, zero legível */
.hero-balance__figure--positive {
  color: #4caf50 !important;
}
.hero-balance__figure--negative {
  color: #ff0000 !important;
}
.finance-label-income {
  color: rgba(76, 175, 80, 0.95) !important;
}
.finance-label-expense {
  color: rgba(255, 0, 0, 0.92) !important;
}
.finance-amount-income {
  color: #4caf50 !important;
}
.finance-amount-expense {
  color: #ff0000 !important;
}

.stat-tile {
  transition:
    box-shadow 0.25s ease,
    transform 0.2s ease,
    border-color 0.2s ease;
}
.stat-tile:hover {
  transform: translateY(-2px);
}

.stat-tile__icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-tile__icon--income {
  background: linear-gradient(145deg, #66bb6a, #4caf50);
  box-shadow: 0 4px 16px rgba(76, 175, 80, 0.45);
}
.stat-tile__icon--expense {
  background: linear-gradient(145deg, #ff1744, #ff0000);
  box-shadow: 0 4px 16px rgba(255, 0, 0, 0.4);
}

.stat-tile__value {
  font-size: clamp(1.15rem, 4vw, 1.5rem);
  line-height: 1.2;
}

.min-w-0 {
  min-width: 0;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
}

.chart-wrap {
  position: relative;
  height: 200px;
}
@media (min-width: 600px) {
  .chart-wrap {
    height: 240px;
  }
}
</style>
