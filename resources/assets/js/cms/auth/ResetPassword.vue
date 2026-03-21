<template>
  <v-app class="cms-auth-v-app">
    <div class="cms-auth-inner cms-auth-inner--wide position-relative">
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
          <div class="cms-auth-logo-wrap text-center mb-6">
            <v-img :src="logoUrl" :alt="appName" contain max-height="52" class="mx-auto" style="max-width: 200px" />
          </div>

          <h1 class="text-h4 font-weight-bold mb-2" :class="titleClass">Nova senha</h1>
          <p class="text-body-2 mb-6" :class="subtitleClass">Defina uma senha forte para sua conta.</p>

          <v-form ref="form" v-model="formValid" class="cms-auth-fields" @submit.prevent="submitReset">
            <v-text-field
              v-model="email"
              outlined
              dense
              hide-details="auto"
              type="email"
              readonly
              prepend-inner-icon="mdi-email-outline"
              label="E-mail"
              class="cms-auth-input mb-3"
              :dark="dark"
              :color="fieldColor"
            />

            <v-text-field
              ref="passwordField"
              v-model="password"
              outlined
              dense
              hide-details="auto"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              prepend-inner-icon="mdi-lock-outline"
              :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              :rules="passwordRules"
              :error-messages="serverErrors.password"
              label="Nova senha"
              hint="Mínimo 8 caracteres, letras e números"
              persistent-hint
              class="cms-auth-input mb-1"
              :dark="dark"
              :color="fieldColor"
              @click:append="showPassword = !showPassword"
              @input="clearServerError('password')"
              @keydown.enter.native="focusNext('confirmField')"
            />

            <div class="mb-4 mt-1">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption" :class="subtitleClass">Força da senha</span>
                <span class="text-caption font-weight-medium" :class="strengthLabelClass">{{ strengthLabel }}</span>
              </div>
              <v-progress-linear
                rounded
                height="4"
                :value="passwordStrengthPercent"
                :color="strengthColor"
                background-color="transparent"
                class="cms-auth-strength"
              />
            </div>

            <v-text-field
              ref="confirmField"
              v-model="passwordConfirmation"
              outlined
              dense
              hide-details="auto"
              :type="showPassword2 ? 'text' : 'password'"
              autocomplete="new-password"
              prepend-inner-icon="mdi-lock-check-outline"
              :append-icon="showPassword2 ? 'mdi-eye-off' : 'mdi-eye'"
              :rules="confirmRules"
              :error-messages="serverErrors.password_confirmation"
              label="Confirmar senha"
              class="cms-auth-input mb-6"
              :dark="dark"
              :color="fieldColor"
              @click:append="showPassword2 = !showPassword2"
              @input="clearServerError('password_confirmation')"
              @keydown.enter.native="submitReset"
            />

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
              Redefinir senha
            </v-btn>
          </v-form>

          <div class="text-center mt-10">
            <a :href="loginUrl" class="cms-auth-link text-body-1 font-weight-medium">Voltar ao login</a>
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
  name: 'CmsResetPassword',
  props: {
    csrfToken: { type: String, required: true },
    resetSubmitUrl: { type: String, required: true },
    loginUrl: { type: String, required: true },
    logoUrl: { type: String, required: true },
    appName: { type: String, default: 'DinDin' },
    resetToken: { type: String, required: true },
    resetEmail: { type: String, default: '' },
    initialErrors: { type: Object, default: () => ({}) },
    oldInput: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      dark: false,
      formValid: true,
      email: '',
      password: '',
      passwordConfirmation: '',
      showPassword: false,
      showPassword2: false,
      submitting: false,
      serverErrors: {},
      passwordRules: [
        (v) => (v != null && String(v) !== '') || 'Defina uma senha',
        (v) => String(v || '').length >= 8 || 'Mínimo 8 caracteres',
        (v) => /[a-zA-Z]/.test(String(v || '')) && /\d/.test(String(v || '')) || 'Use letras e números',
      ],
    }
  },
  computed: {
    titleClass() {
      return this.dark ? 'white--text' : 'grey--text text--darken-4'
    },
    subtitleClass() {
      return this.dark ? 'grey--text' : 'grey--text text--darken-1'
    },
    fieldColor() {
      return this.dark ? 'white' : 'primary'
    },
    confirmRules() {
      return [
        (v) => (v != null && String(v) !== '') || 'Confirme a senha',
        (v) => v === this.password || 'As senhas não coincidem',
      ]
    },
    passwordStrengthScore() {
      const p = this.password || ''
      if (!p) return 0
      let s = 0
      if (p.length >= 8) s++
      if (/[a-zA-Z]/.test(p)) s++
      if (/\d/.test(p)) s++
      if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++
      if (/[^a-zA-Z0-9]/.test(p)) s++
      return Math.min(s, 4)
    },
    passwordStrengthPercent() {
      return (this.passwordStrengthScore / 4) * 100
    },
    strengthColor() {
      const sc = this.passwordStrengthScore
      if (sc <= 1) return 'error'
      if (sc === 2) return 'warning'
      if (sc === 3) return 'info'
      return 'success'
    },
    strengthLabel() {
      const labels = ['—', 'Fraca', 'Média', 'Boa', 'Forte']
      return labels[this.passwordStrengthScore] || '—'
    },
    strengthLabelClass() {
      return this.strengthColor + '--text'
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
    this.email = this.resetEmail || (this.oldInput.email ? String(this.oldInput.email) : '')
    if (this.oldInput.email && !this.email) this.email = String(this.oldInput.email)
    this.$nextTick(() => {
      const c = this.$refs.passwordField
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
    focusNext(refName) {
      const c = this.$refs[refName]
      const el = c && (c.$refs.input || c.$refs.input_)
      if (el) el.focus()
    },
    submitReset() {
      if (!this.$refs.form || !this.$refs.form.validate()) return
      this.submitting = true
      submitNativePost(this.resetSubmitUrl, {
        _token: this.csrfToken,
        token: this.resetToken,
        email: String(this.email || '').trim(),
        password: String(this.password || ''),
        password_confirmation: String(this.passwordConfirmation || ''),
      })
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
}
.cms-auth-link:hover {
  opacity: 0.88;
  text-decoration: underline;
}
</style>
