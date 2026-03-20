<template>
  <div class="projection-page px-1">
    <p class="text-caption secondary--text mb-4">
      Estimativa dos próximos doze meses com base na sua média recente e parcelas cadastradas.
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
