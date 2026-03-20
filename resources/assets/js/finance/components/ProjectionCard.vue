<template>
  <v-card class="proj-card rounded-xl" flat outlined>
    <v-card-text class="pb-2 pt-5 px-4 px-sm-5">
      <div class="d-flex align-center flex-wrap mb-4">
        <v-icon color="secondary" class="mr-2">mdi-chart-line-variant</v-icon>
        <span class="text-h6 font-weight-bold">Projeção</span>
        <v-chip x-small outlined class="ml-2" color="secondary">12 meses</v-chip>
        <v-spacer />
        <v-tooltip bottom max-width="280">
          <template #activator="{ on, attrs }">
            <v-btn icon small v-bind="attrs" aria-label="Como calculamos" v-on="on">
              <v-icon small color="secondary">mdi-information-outline</v-icon>
            </v-btn>
          </template>
          <span v-if="meta">
            Média dos meses {{ metaReferenciaLabel }} (sem parcelas na média) + parcelas futuras
            quando cadastradas.
          </span>
          <span v-else>Estimativa com base no seu histórico e parcelas.</span>
        </v-tooltip>
      </div>
      <p
        v-if="meta && meta.parcelas_detectadas"
        class="text-caption secondary--text mt-n3 mb-4 px-0"
      >
        {{ meta.parcelas_detectadas }} fonte(s) de parcela no cálculo
      </p>
    </v-card-text>

    <template v-if="loading">
      <v-row dense class="px-3 px-sm-4 pb-5">
        <v-col v-for="n in 12" :key="n" cols="12" md="4">
          <v-skeleton-loader type="card" height="140" class="rounded-xl" boilerplate />
        </v-col>
      </v-row>
    </template>

    <div v-else-if="displayRows.length" class="px-3 px-sm-4 pb-5">
      <v-row dense>
        <v-col v-for="r in displayRows" :key="r.mes" cols="12" md="4">
          <v-card
            class="proj-month rounded-xl pa-4 h-100"
            :class="monthCardClass(r.saldo_projetado)"
            flat
            outlined
          >
            <div class="text-overline secondary--text font-weight-medium mb-2">
              {{ monthHeading(r) }}
            </div>
            <div
              class="proj-month__saldo tabular-nums font-weight-bold"
              :class="saldoTextClass(r.saldo_projetado)"
            >
              {{ formatSaldoLine(r.saldo_projetado) }}
            </div>
            <div class="text-caption secondary--text mt-1 mb-3">Saldo projetado</div>

            <v-divider class="mb-3 opacity-60" />

            <div class="d-flex justify-space-between text-caption">
              <span class="secondary--text">Receitas</span>
              <span class="finance-amount-income font-weight-medium tabular-nums">
                {{ formatBRL(r.receitas_previstas) }}
              </span>
            </div>
            <div class="d-flex justify-space-between text-caption mt-1">
              <span class="secondary--text">Despesas</span>
              <span class="finance-amount-expense font-weight-medium tabular-nums">
                {{ formatBRL(r.despesas_previstas) }}
              </span>
            </div>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <v-card-text v-else class="finance-text-muted text-body-2 pb-6">
      Não foi possível carregar a projeção.
    </v-card-text>
  </v-card>
</template>

<script>
import axios from 'axios'

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
      meta: null,
    }
  },
  computed: {
    /** Meses projetados retornados pela API. */
    displayRows() {
      return this.rows || []
    },
    metaReferenciaLabel() {
      if (!this.meta || !this.meta.meses_referencia || !this.meta.meses_referencia.length) return '—'
      return this.meta.meses_referencia.join(', ')
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
    monthHeading(r) {
      return r.label || r.mes || '—'
    },
    formatSaldoLine(saldo) {
      const n = Number(saldo) || 0
      const abs = this.formatBRL(Math.abs(n))
      if (n > 0) return `+ ${abs}`
      if (n < 0) return `− ${abs}`
      return this.formatBRL(0)
    },
    saldoTextClass(saldo) {
      const n = Number(saldo)
      if (n > 0) return 'proj-month__saldo--pos'
      if (n < 0) return 'proj-month__saldo--neg'
      return 'secondary--text'
    },
    monthCardClass(saldo) {
      const n = Number(saldo)
      if (n > 0) return 'proj-month--pos'
      if (n < 0) return 'proj-month--neg'
      return 'proj-month--neu'
    },
    async load() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) {
        this.loading = false
        return
      }
      this.loading = true
      try {
        const { data } = await axios.get(`${base}/projection`)
        this.rows = data.meses || []
        this.meta = data.meta || null
        this.$emit('loaded', data)
      } catch (e) {
        this.rows = []
        this.meta = null
        this.$emit('error', 'Não foi possível carregar a projeção.')
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.proj-month {
  transition:
    box-shadow 0.25s ease,
    transform 0.2s ease;
}

.proj-month:hover {
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.35);
  transform: translateY(-2px);
}

.proj-month--pos {
  border-left: 4px solid #4caf50 !important;
  background: linear-gradient(180deg, rgba(76, 175, 80, 0.12) 0%, rgba(53, 57, 66, 0.98) 55%) !important;
}

.proj-month--neg {
  border-left: 4px solid #ff0000 !important;
  background: linear-gradient(180deg, rgba(255, 0, 0, 0.1) 0%, rgba(53, 57, 66, 0.98) 55%) !important;
}

.proj-month--neu {
  border-left: 4px solid rgba(133, 136, 143, 0.8) !important;
}

.proj-month__saldo {
  font-size: clamp(1.35rem, 4vw, 1.65rem);
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.proj-month__saldo--pos {
  color: #4caf50 !important;
}

.proj-month__saldo--neg {
  color: #ff0000 !important;
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
