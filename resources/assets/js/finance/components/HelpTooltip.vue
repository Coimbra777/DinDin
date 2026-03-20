<template>
  <!-- Mobile / touch: menu ao toque (fecha ao clicar fora) -->
  <v-menu
    v-if="useMenu"
    offset-y
    :close-on-content-click="true"
    transition="fade-transition"
    content-class="help-tooltip-menu"
  >
    <template slot="activator" slot-scope="{ on, attrs }">
      <v-btn
        icon
        x-small
        type="button"
        class="help-tooltip__btn"
        v-bind="attrs"
        v-on="on"
        :aria-label="ariaLabel"
      >
        <v-icon :size="iconSize" :color="iconColor">{{ icon }}</v-icon>
      </v-btn>
    </template>
    <v-card :max-width="maxWidth" class="help-tooltip__card pa-3 text-body-2 rounded-lg">
      {{ text }}
    </v-card>
  </v-menu>
  <!-- Desktop: tooltip ao hover / foco -->
  <v-tooltip v-else bottom :max-width="maxWidth" :open-delay="openDelay" :close-delay="closeDelay">
    <template slot="activator" slot-scope="{ on, attrs }">
      <v-btn
        icon
        x-small
        type="button"
        class="help-tooltip__btn"
        v-bind="attrs"
        v-on="on"
        :aria-label="ariaLabel"
      >
        <v-icon :size="iconSize" :color="iconColor">{{ icon }}</v-icon>
      </v-btn>
    </template>
    <span class="help-tooltip__tip">{{ text }}</span>
  </v-tooltip>
</template>

<script>
export default {
  name: 'HelpTooltip',
  props: {
    text: { type: String, required: true },
    /** Largura máxima do texto (px) */
    maxWidth: { type: [Number, String], default: 280 },
    /** Acessibilidade */
    ariaLabel: { type: String, default: 'Ajuda' },
    icon: { type: String, default: 'mdi-help-circle' },
    iconColor: { type: String, default: 'secondary' },
    iconSize: { type: [Number, String], default: 18 },
    openDelay: { type: Number, default: 150 },
    closeDelay: { type: Number, default: 80 },
  },
  computed: {
    useMenu() {
      return this.$vuetify.breakpoint.smAndDown
    },
  },
}
</script>

<style scoped>
.help-tooltip__btn {
  opacity: 0.55;
  transition: opacity 0.15s ease;
}
.help-tooltip__btn:hover,
.help-tooltip__btn:focus {
  opacity: 1;
}
.help-tooltip__card {
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12) !important;
}
.help-tooltip__tip {
  display: inline-block;
  line-height: 1.45;
}
</style>
