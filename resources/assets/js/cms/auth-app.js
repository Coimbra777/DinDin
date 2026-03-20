/**
 * CMS — Login e cadastro (Vue 2 + Vuetify 2).
 * Mantém POST tradicional + CSRF (sem alterar guards Laravel).
 */
import Vue from 'vue'
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'
import '@mdi/font/css/materialdesignicons.css'
import VueMask from 'v-mask'
import Login from './auth/Login.vue'
import Register from './auth/Register.vue'
import { getInitialDark, applyAuthBodyClass } from './auth/cmsAuthTheme'
import '../../sass/cms-auth.scss'

Vue.use(Vuetify)
Vue.use(VueMask)

const el = document.getElementById('cms-auth-app')
if (el) {
  document.documentElement.classList.add('cms-auth-html')

  const page = el.dataset.page || 'login'
  const csrfToken = el.dataset.csrf || ''
  const loginUrl = el.dataset.loginUrl || ''
  const registerUrl = el.dataset.registerUrl || ''
  const forgotUrl = el.dataset.forgotUrl || '#'
  const logoUrl = el.dataset.logoUrl || '/logowhite.png'
  const appName = el.dataset.appName || 'App'

  /** Garante objeto plano (Blade/@json pode enviar [] em casos legados). */
  const asObject = (raw, fallback = {}) => {
    try {
      const v = typeof raw === 'string' ? JSON.parse(raw || '{}') : raw
      if (v && typeof v === 'object' && !Array.isArray(v)) return v
    } catch (e) {
      /* ignore */
    }
    return fallback
  }

  const initialErrors = asObject(el.dataset.errors, {})
  const oldInput = asObject(el.dataset.old, {})

  const initialDark = getInitialDark()
  applyAuthBodyClass(initialDark)

  const Root = page === 'register' ? Register : Login

  const props =
    page === 'register'
      ? {
          csrfToken,
          registerUrl,
          loginUrl,
          logoUrl,
          appName,
          initialErrors,
          oldInput,
        }
      : {
          csrfToken,
          loginUrl,
          registerUrl,
          forgotUrl,
          logoUrl,
          appName,
          initialErrors,
          oldInput,
        }

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
            error: '#ff0000',
            success: '#4CAF50',
            info: '#1976D2',
            warning: '#FFB300',
            anchor: '#2c2f36',
          },
          dark: {
            primary: '#ff0000',
            secondary: '#85888f',
            accent: '#ff3333',
            error: '#ff5252',
            success: '#66BB6A',
            info: '#64B5F6',
            warning: '#FFB300',
            anchor: '#ffffff',
          },
        },
      },
    }),
    render: (h) => h(Root, { props }),
  }).$mount(el)
}
