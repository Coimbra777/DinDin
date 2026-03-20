<template>
  <credit-cards-panel
    :api-base="apiBase"
    :refresh-key="refreshKey"
    @saved="$emit('saved')"
    @error="$emit('error', $event)"
  />
</template>

<script>
import CreditCardsPanel from '../../components/CreditCardsPanel.vue'

export default {
  name: 'CreditCardsPage',
  components: { CreditCardsPanel },
  props: {
    apiBase: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
}
</script>
