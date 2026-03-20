<template>
  <div class="insights-page px-1">
    <p class="text-caption secondary--text mb-4">
      Resumo automático do mês selecionado: categorias e comparação com o mês anterior.
    </p>

    <v-card class="rounded-xl mb-4" flat outlined>
      <v-card-text>
        <div class="d-flex align-center mb-3">
          <v-icon color="secondary" class="mr-2">mdi-lightbulb-outline</v-icon>
          <span class="text-h6 font-weight-bold">Insights</span>
          <v-spacer />
          <v-btn icon small :loading="loading" aria-label="Atualizar" @click="load">
            <v-icon small>mdi-refresh</v-icon>
          </v-btn>
        </div>

        <v-progress-linear v-if="loading && !insights.length" indeterminate color="primary" class="mb-2" />

        <div v-if="insights.length">
          <v-alert
            v-for="(ins, i) in insights"
            :key="'ins-' + i"
            dense
            text
            type="info"
            border="left"
            colored-border
            class="mb-2 text-body-2"
          >
            {{ ins.message }}
          </v-alert>
        </div>
        <p v-else-if="!loading" class="finance-text-muted text-body-2 mb-0">
          Nenhuma frase destacada para este mês. Veja a tabela de categorias abaixo.
        </p>
      </v-card-text>
    </v-card>

    <v-row v-if="comparacao.mes_anterior" dense>
      <v-col cols="12" md="4">
        <v-card class="rounded-xl pa-4" flat outlined>
          <div class="text-overline secondary--text">Despesas {{ labelMes(comparacao.mes_atual) }}</div>
          <div class="text-h6 font-weight-bold tabular-nums">{{ formatBRL(comparacao.despesa_mes_atual) }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card class="rounded-xl pa-4" flat outlined>
          <div class="text-overline secondary--text">Despesas {{ labelMes(comparacao.mes_anterior) }}</div>
          <div class="text-h6 font-weight-bold tabular-nums">{{ formatBRL(comparacao.despesa_mes_anterior) }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card class="rounded-xl pa-4" flat outlined>
          <div class="text-overline secondary--text">Variação</div>
          <div
            class="text-h6 font-weight-bold tabular-nums"
            :class="variacaoClass(comparacao.variacao_percentual)"
          >
            {{ formatVariacao(comparacao.variacao_percentual) }}
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-card class="rounded-xl mt-4" flat outlined>
      <v-card-title class="subtitle-1 font-weight-bold py-4">
        Despesas por categoria (%)
      </v-card-title>
      <v-divider />
      <v-data-table
        :headers="headers"
        :items="categorias"
        :loading="loading"
        dense
        class="transparent"
        hide-default-footer
        :items-per-page="-1"
        mobile-breakpoint="0"
      >
        <template #item.despesa="{ item }">
          <span class="tabular-nums">{{ formatBRL(item.despesa) }}</span>
        </template>
        <template #item.percentual_das_despesas="{ item }">
          {{ pctLabel(item.percentual_das_despesas) }}
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'InsightsPage',
  props: {
    apiBase: { type: String, required: true },
    month: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: false,
      insights: [],
      categorias: [],
      comparacao: {},
      headers: [
        { text: 'Categoria', value: 'category_name' },
        { text: 'Despesa', value: 'despesa', align: 'end' },
        { text: '% das despesas', value: 'percentual_das_despesas', align: 'end' },
      ],
    }
  },
  watch: {
    month() {
      this.load()
    },
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
    labelMes(ym) {
      if (!ym || typeof ym !== 'string') return '—'
      const [y, m] = ym.split('-')
      return `${m}/${y}`
    },
    formatVariacao(pct) {
      if (pct == null) return '—'
      const n = Number(pct)
      if (n > 0) return `+${n.toFixed(1)}%`
      return `${n.toFixed(1)}%`
    },
    variacaoClass(pct) {
      const n = Number(pct)
      if (n > 5) return 'finance-amount-expense'
      if (n < -5) return 'finance-amount-income'
      return ''
    },
    pctLabel(v) {
      return v != null ? `${v}%` : '—'
    },
    async load() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) return
      this.loading = true
      try {
        const { data } = await axios.get(`${base}/insights`, { params: { month: this.month } })
        this.insights = data.insights || []
        this.categorias = data.categorias || []
        this.comparacao = data.comparacao_mes_anterior || {}
      } catch (e) {
        this.insights = []
        this.categorias = []
        this.comparacao = {}
        this.$emit('error', 'Não foi possível carregar insights.')
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.insights-page {
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}
.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
