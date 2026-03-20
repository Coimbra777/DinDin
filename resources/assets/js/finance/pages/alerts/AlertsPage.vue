<template>
  <div class="alerts-page px-1">
    <p class="text-caption secondary--text mb-4">
      Alertas automáticos com base no mês selecionado acima e no seu histórico recente.
    </p>

    <v-card class="rounded-xl" flat outlined>
      <v-card-text class="pb-2">
        <div class="d-flex align-center mb-2">
          <v-icon color="secondary" class="mr-2">mdi-bell-alert-outline</v-icon>
          <span class="text-h6 font-weight-bold">Alertas</span>
          <v-spacer />
          <v-btn icon small :loading="loading" aria-label="Atualizar" @click="load">
            <v-icon small>mdi-refresh</v-icon>
          </v-btn>
        </div>
      </v-card-text>

      <v-card-text v-if="loading && !alerts.length" class="text-center py-8">
        <v-progress-circular indeterminate color="primary" />
      </v-card-text>

      <v-card-text v-else-if="!alerts.length" class="finance-text-muted text-body-2 py-6">
        Nenhum alerta para este mês. Continue acompanhando seus gastos e cartões.
      </v-card-text>

      <v-list v-else class="py-0 transparent" dense>
        <v-list-item v-for="(a, i) in alerts" :key="i" class="px-0">
          <v-list-item-content>
            <v-alert
              dense
              border="left"
              colored-border
              :type="alertType(a.severity)"
              class="mb-0 text-body-2"
              text
            >
              {{ a.message }}
              <template v-if="a.meta && formatMeta(a.meta)">
                <div class="text-caption mt-1 secondary--text">
                  {{ formatMeta(a.meta) }}
                </div>
              </template>
            </v-alert>
          </v-list-item-content>
        </v-list-item>
      </v-list>
    </v-card>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AlertsPage',
  props: {
    apiBase: { type: String, required: true },
    month: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: false,
      alerts: [],
      loadedMonth: '',
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
    alertType(severity) {
      if (severity === 'warning') return 'warning'
      return 'info'
    },
    formatMeta(meta) {
      const parts = []
      if (meta.saldo_com_cartao != null) {
        parts.push(`Saldo c/ cartão: ${this.$formatCurrencyBRL(meta.saldo_com_cartao)}`)
      }
      if (meta.percentual_acima_media != null) {
        parts.push(`Acima da média: ${meta.percentual_acima_media}%`)
      }
      if (meta.fatura_total != null && meta.limite != null) {
        parts.push(`Fatura ${this.$formatCurrencyBRL(meta.fatura_total)} / limite ${this.$formatCurrencyBRL(meta.limite)}`)
      }
      return parts.join(' · ')
    },
    async load() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) return
      this.loading = true
      try {
        const { data } = await axios.get(`${base}/alerts`, { params: { month: this.month } })
        this.alerts = data.alerts || []
        this.loadedMonth = data.month || this.month
      } catch (e) {
        this.alerts = []
        this.$emit('error', 'Não foi possível carregar alertas.')
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.alerts-page {
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}
</style>
