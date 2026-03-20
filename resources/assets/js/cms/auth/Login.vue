<template>
  <v-app class="cms-auth-v-app">
    <div class="cms-auth-inner position-relative">
      <v-btn
        icon
        class="cms-auth-theme-btn"
        :class="dark ? 'white--text' : 'grey--text text--darken-2'"
        aria-label="Alternar tema claro ou escuro"
        @click="toggleTheme"
      >
        <v-icon>{{ dark ? 'mdi-white-balance-sunny' : 'mdi-moon-waning-crescent' }}</v-icon>
      </v-btn>

      <transition appear name="cms-auth-fade">
        <div>
          <div class="cms-auth-logo-wrap text-center mb-8">
            <v-img :src="logoUrl" :alt="appName" contain max-height="56" class="mx-auto" style="max-width: 220px" />
          </div>

          <h1 class="text-h4 font-weight-bold mb-2" :class="titleClass">Entrar</h1>
          <p class="text-body-1 mb-8" :class="subtitleClass">Painel administrativo</p>

          <v-form ref="form" v-model="formValid" lazy-validation class="cms-auth-fields" @submit.prevent="submitLogin">
            <v-text-field
              ref="firstField"
              v-model="username"
              solo
              flat
              dense
              hide-details="auto"
              autocomplete="username"
              prepend-inner-icon="mdi-account-outline"
              :rules="usernameRules"
              :error-messages="serverErrors.username"
              label="E-mail ou nome"
              placeholder=" "
              validate-on-blur
              class="cms-auth-input mb-1"
              :dark="dark"
              @input="clearServerError('username')"
              @keydown.enter.native="focusPassword"
            />

            <v-text-field
              ref="passwordField"
              v-model="password"
              solo
              flat
              dense
              hide-details="auto"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              prepend-inner-icon="mdi-lock-outline"
              :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              :rules="passwordRules"
              :error-messages="serverErrors.password"
              label="Senha"
              placeholder=" "
              validate-on-blur
              class="cms-auth-input mb-2"
              :dark="dark"
              @click:append="showPassword = !showPassword"
              @input="clearServerError('password')"
              @keydown.enter.native="submitLogin"
            />

            <div class="d-flex align-center mb-8">
              <v-checkbox
                v-model="remember"
                label="Lembrar-me"
                dense
                hide-details
                class="mt-0 pt-0 cms-auth-checkbox"
                color="primary"
                :dark="dark"
              />
            </div>

            <v-btn
              type="submit"
              block
              x-large
              depressed
              rounded
              color="primary"
              class="font-weight-bold text-none elevation-2"
              :loading="submitting"
            >
              Entrar
            </v-btn>
          </v-form>

          <div class="text-center mt-10">
            <a :href="registerUrl" class="cms-auth-link d-inline-block mb-3 text-body-1 font-weight-medium"> Criar conta </a>
            <br />
            <a :href="forgotUrl" class="cms-auth-link cms-auth-link--muted text-body-2"> Esqueci minha senha </a>
          </div>
        </div>
      </transition>
    </div>
  </v-app>
</template>

<script>
import { getInitialDark, persistTheme, applyAuthBodyClass } from './cmsAuthTheme'
import { submitNativePost } from './submitNativePost'

export default {
  name: 'CmsLogin',
  props: {
    csrfToken: { type: String, required: true },
    loginUrl: { type: String, required: true },
    registerUrl: { type: String, required: true },
    forgotUrl: { type: String, required: true },
    logoUrl: { type: String, required: true },
    appName: { type: String, default: 'DinDin' },
    initialErrors: { type: Object, default: () => ({}) },
    oldInput: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      dark: false,
      formValid: true,
      username: '',
      password: '',
      remember: false,
      showPassword: false,
      submitting: false,
      serverErrors: {},
      usernameRules: [
        (v) => (v != null && String(v).trim() !== '') || 'Informe seu e-mail ou nome',
        (v) => String(v || '').trim().length >= 2 || 'Mínimo 2 caracteres',
      ],
      passwordRules: [(v) => (v != null && String(v) !== '') || 'Informe a senha'],
    }
  },
  computed: {
    titleClass() {
      return this.dark ? 'white--text' : 'grey--text text--darken-4'
    },
    subtitleClass() {
      return this.dark ? 'grey--text' : 'grey--text text--darken-1'
    },
  },
  watch: {
    dark(v) {
      this.$vuetify.theme.dark = v
      persistTheme(v)
      applyAuthBodyClass(v)
    },
  },
  mounted() {
    this.dark = getInitialDark()
    this.$vuetify.theme.dark = this.dark
    applyAuthBodyClass(this.dark)
    this.serverErrors = this.normalizeServerErrors(this.initialErrors)
    if (this.oldInput.username != null && this.oldInput.username !== '') {
      this.username = String(this.oldInput.username)
    }
    if (this.oldInput.remember === '1' || this.oldInput.remember === true || this.oldInput.remember === 1) {
      this.remember = true
    }
    this.$nextTick(() => {
      const c = this.$refs.firstField
      const el = c && (c.$refs.input || c.$refs.input_)
      if (el) el.focus()
    })
  },
  methods: {
    normalizeServerErrors(raw) {
      const out = {}
      if (!raw || typeof raw !== 'object') return out
      Object.keys(raw).forEach((k) => {
        const v = raw[k]
        out[k] = Array.isArray(v) ? v : [String(v)]
      })
      return out
    },
    toggleTheme() {
      this.dark = !this.dark
    },
    clearServerError(key) {
      if (this.serverErrors[key]) {
        const next = { ...this.serverErrors }
        delete next[key]
        this.serverErrors = next
      }
    },
    focusPassword() {
      const c = this.$refs.passwordField
      const el = c && (c.$refs.input || c.$refs.input_)
      if (el) el.focus()
    },
    submitLogin() {
      if (!this.$refs.form || !this.$refs.form.validate()) return
      this.submitting = true
      const payload = {
        _token: this.csrfToken,
        username: String(this.username || '').trim(),
        password: String(this.password || ''),
      }
      if (this.remember) {
        payload.remember = '1'
      }
      submitNativePost(this.loginUrl, payload)
    },
  },
}
</script>

<style scoped>
.cms-auth-theme-btn {
  position: absolute;
  top: 0;
  right: 0;
  z-index: 2;
}
.cms-auth-link {
  color: #ff0000 !important;
  text-decoration: none;
  transition: opacity 0.2s ease;
}
.cms-auth-link:hover {
  opacity: 0.85;
  text-decoration: underline;
}
.cms-auth-link--muted {
  color: #85888f !important;
}
.theme--dark .cms-auth-link--muted {
  color: rgba(255, 255, 255, 0.55) !important;
}
.theme--dark .cms-auth-link--muted:hover {
  color: #ff6666 !important;
}
</style>
