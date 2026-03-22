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
        Nenhum alerta para este mês. Continue acompanhando seus gastos.
      </v-card-text>

      <v-list v-else class="py-0 transparent" dense>
        <v-list-item v-for="(a, i) in alerts" :key="i" class="px-0">
          <v-list-item-content>
            <v-alert
              dense
              border="left"
              colored-border
              :type="alertType(a.severity)"
              class="mb-0 finance-alert-item"
              text
            >
              <div class="text-subtitle-2 font-weight-bold">{{ displayTitle(a) }}</div>
              <div class="text-body-2 mt-1">{{ a.message }}</div>
              <div v-if="a.action_hint" class="text-caption mt-2 finance-alert-hint">
                {{ a.action_hint }}
              </div>
              <template v-if="a.meta && formatMeta(a.meta)">
                <div class="text-caption mt-2 secondary--text">
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
    displayTitle(a) {
      if (a.title) return a.title
      return 'Alerta'
    },
    formatMeta(meta) {
      const parts = []
      if (meta.saldo != null) {
        parts.push(`Saldo do mês: ${this.$formatCurrencyBRL(meta.saldo)}`)
      }
      if (meta.percentual_acima_media != null) {
        parts.push(`Acima da média: ${meta.percentual_acima_media}%`)
      }
      if (meta.shortfall != null && meta.projected_total != null) {
        parts.push(
          `Projeção no prazo: ${this.$formatCurrencyBRL(meta.projected_total)} · faltariam ${this.$formatCurrencyBRL(meta.shortfall)}`
        )
      }
      if (meta.days_remaining != null) {
        parts.push(`${meta.days_remaining} dias até o prazo da meta`)
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
.finance-alert-hint {
  font-weight: 500;
  opacity: 0.95;
}
.theme--light .finance-alert-hint {
  color: rgba(0, 0, 0, 0.72) !important;
}
.theme--dark .finance-alert-hint {
  color: rgba(255, 255, 255, 0.85) !important;
}
</style>
