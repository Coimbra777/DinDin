<template>
  <div class="reports-page">
    <p class="text-caption secondary--text mb-4 px-1">
      Use o <strong>Mês</strong> acima para o gráfico de <strong>categorias</strong>.
      A aba <strong>Mensal</strong> só é carregada ao abri-la (últimos 6 ou 12 meses).
    </p>

    <v-card class="rounded-xl overflow-hidden mb-4" flat outlined>
      <v-tabs v-model="tab" grow background-color="transparent" slider-color="primary" class="reports-tabs finance-tabs">
        <v-tab class="font-weight-medium">
          <v-icon left small>mdi-chart-donut</v-icon>
          Categorias
        </v-tab>
        <v-tab class="font-weight-medium">
          <v-icon left small>mdi-chart-bar</v-icon>
          Mensal
        </v-tab>
      </v-tabs>

      <v-tabs-items v-model="tab" class="reports-tabs-items transparent">
        <v-tab-item class="pa-3 pa-sm-4">
          <report-category-pie-card
            v-if="tab === 0"
            :key="'pie-' + month + '-' + refreshKey"
            :api-base="apiBase"
            :month="month"
            :refresh-key="refreshKey"
            @error="$emit('error', $event)"
          />
        </v-tab-item>
        <v-tab-item class="pa-3 pa-sm-4">
          <report-monthly-bar-card
            v-if="tab === 1"
            :key="'bar-' + refreshKey"
            :api-base="apiBase"
            :refresh-key="refreshKey"
            @error="$emit('error', $event)"
          />
        </v-tab-item>
      </v-tabs-items>
    </v-card>
  </div>
</template>

<script>
import ReportCategoryPieCard from '../../components/reports/ReportCategoryPieCard.vue'
import ReportMonthlyBarCard from '../../components/reports/ReportMonthlyBarCard.vue'

export default {
  name: 'ReportsPage',
  components: { ReportCategoryPieCard, ReportMonthlyBarCard },
  props: {
    apiBase: { type: String, required: true },
    month: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      tab: 0,
    }
  },
}
</script>

<style scoped>
.reports-page {
  max-width: 900px;
  margin-left: auto;
  margin-right: auto;
}

.reports-tabs >>> .v-tab {
  text-transform: none;
  letter-spacing: 0.01em;
}

.reports-tabs-items {
  background: transparent !important;
}
</style>
