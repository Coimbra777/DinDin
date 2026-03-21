<template>
  <div class="projection-page px-1">
    <p class="text-caption secondary--text mb-4">
      Próximos <strong>12 meses</strong> a partir do mês seguinte: cada coluna reflete apenas os
      <strong>lançamentos futuros</strong> que já criaste com data nesse mês. Não há simulação nem cópia de meses
      anteriores — valores batem com Movimentações ao filtrar o mesmo mês.
    </p>
    <projection-card
      :api-base="apiBase"
      :refresh-key="refreshKey"
      @error="$emit('error', $event)"
    />
  </div>
</template>

<script>
import ProjectionCard from '../../components/ProjectionCard.vue'

export default {
  name: 'ProjectionPage',
  components: { ProjectionCard },
  props: {
    apiBase: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
}
</script>

<style scoped>
.projection-page {
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}
</style>
