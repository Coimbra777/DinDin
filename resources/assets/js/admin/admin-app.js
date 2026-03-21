/**
 * Admin — Vue 2 + Vuetify 2 (utilizadores e módulos SaaS).
 * Tema alinhado às finanças (primary #ff0000, light/dark + finance-standalone.scss).
 */
import Vue from 'vue'
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'
import '@mdi/font/css/materialdesignicons.css'
import axios from 'axios'
import AdminApp from './AdminApp.vue'
import { getInitialDark, applyBodyThemeClass } from '../finance/financeTheme'
import '../../sass/finance-standalone.scss'

Vue.use(Vuetify)

const el = document.getElementById('admin-app')
if (el) {
  const initialDark = getInitialDark()
  applyBodyThemeClass(initialDark)

  const meta = document.querySelector('meta[name="csrf-token"]')
  const csrf = meta ? meta.getAttribute('content') : null
  if (csrf) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
  }
  axios.defaults.headers.common.Accept = 'application/json'

  // eslint-disable-next-line no-new
  new Vue({
    vuetify: new Vuetify({
      icons: { iconfont: 'mdi' },
      theme: {
        dark: initialDark,
        options: { customProperties: true },
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
      h(AdminApp, {
        props: {
          apiBase: (el.dataset.apiBase || '').replace(/\/$/, ''),
          userName: el.dataset.userName || '',
          financePanelUrl: el.dataset.financePanelUrl || '/cms/finance/finance_dashboard',
        },
      }),
  }).$mount(el)
}
