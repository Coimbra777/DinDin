<template>
  <v-tooltip bottom>
    <template slot="activator" slot-scope="{ on, attrs }">
      <v-btn
        icon
        class="finance-theme-toggle mr-1"
        :aria-label="ariaLabel"
        v-bind="attrs"
        v-on="on"
        @click="toggle"
      >
        <v-icon aria-hidden="true">{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
      </v-btn>
    </template>
    <span>{{ isDark ? 'Modo claro ☀️' : 'Modo escuro 🌙' }}</span>
  </v-tooltip>
</template>

<script>
import { persistTheme, applyBodyThemeClass } from '../financeTheme'

/**
 * Alterna light/dark do Vuetify e persiste em localStorage (`finance-theme-preference`).
 * Combina com `getInitialDark()` + `prefers-color-scheme` em finance-app.js.
 */
export default {
  name: 'FinanceThemeToggle',
  computed: {
    isDark() {
      return this.$vuetify.theme.dark
    },
    ariaLabel() {
      return this.isDark ? 'Ativar modo claro' : 'Ativar modo escuro'
    },
  },
  methods: {
    toggle() {
      this.$vuetify.theme.dark = !this.$vuetify.theme.dark
      persistTheme(this.$vuetify.theme.dark)
      applyBodyThemeClass(this.$vuetify.theme.dark)
    },
  },
}
</script>

<style scoped>
.finance-theme-toggle {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.finance-theme-toggle:hover {
  transform: rotate(-8deg) scale(1.05);
}
</style>
