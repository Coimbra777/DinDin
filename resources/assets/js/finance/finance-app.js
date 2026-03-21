/**
 * Finanças — Vue 2 + Vuetify 2 (SPA montada em #finance-app).
 *
 * Temas light/dark: `theme.themes.light` · `theme.themes.dark` + `finance-standalone.scss`.
 * Preferência: localStorage (`finance-theme-preference`) ou, se vazio, `prefers-color-scheme`.
 *
 * Cores: primary #ff0000, secondary #85888f, success #4CAF50 (receita), error #ff0000 (despesa).
 */
import Vue from 'vue'
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'
import '@mdi/font/css/materialdesignicons.css'
import axios from 'axios'
import FinanceApp from './pages/finance/FinanceApp.vue'
import { formatCurrencyBRL } from './currency'
import { getInitialDark, applyBodyThemeClass } from './financeTheme'
import '../../sass/finance-standalone.scss'

Vue.use(Vuetify)
Vue.prototype.$formatCurrencyBRL = formatCurrencyBRL

function parseUserModuleSlugs() {
  const script = document.getElementById('finance-user-module-slugs')
  if (script && script.textContent) {
    try {
      const parsed = JSON.parse(script.textContent.trim())
      return Array.isArray(parsed) ? parsed.map(String) : []
    } catch (e) {
      return []
    }
  }
  return []
}

const el = document.getElementById('finance-app')
if (el) {
  const initialDark = getInitialDark()
  applyBodyThemeClass(initialDark)

  const meta = document.querySelector('meta[name="csrf-token"]')
  const csrf = meta ? meta.getAttribute('content') : null
  if (csrf) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
  }

  // eslint-disable-next-line no-new
  new Vue({
    vuetify: new Vuetify({
      icons: { iconfont: 'mdi' },
      theme: {
        dark: initialDark,
        options: {
          customProperties: true,
        },
        themes: {
          light: {
            primary: '#ff0000',
            secondary: '#85888f',
            accent: '#ff3333',
            success: '#4CAF50',
            error: '#ff0000',
            info: '#1976D2',
            warning: '#FFB300',
            anchor: '#2c2f36',
            background: '#ffffff',
            surface: '#f5f5f5',
          },
          dark: {
            primary: '#ff0000',
            secondary: '#85888f',
            accent: '#ff3333',
            success: '#4CAF50',
            error: '#ff0000',
            info: '#64B5F6',
            warning: '#FFB300',
            anchor: '#ffffff',
            background: '#2c2f36',
            surface: '#2c2f36',
          },
        },
      },
    }),
    render: (h) =>
      h(FinanceApp, {
        props: {
          initialView: el.dataset.initialView || 'dashboard',
          initialMonth: el.dataset.initialMonth || '',
          apiBase: (el.dataset.apiBase || '').replace(/\/$/, ''),
          userName: el.dataset.userName || '',
          onboardingInitialCompleted: el.dataset.onboardingCompleted === '1',
          onboardingCompleteUrl: el.dataset.onboardingCompleteUrl || '',
          isAdmin: el.dataset.isAdmin === '1',
          userModuleSlugs: parseUserModuleSlugs(),
        },
      }),
  }).$mount(el)
}
