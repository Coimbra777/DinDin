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
          <div class="cms-auth-logo-wrap text-center mb-6">
            <v-img :src="logoUrl" :alt="appName" contain max-height="56" class="mx-auto" style="max-width: 220px" />
          </div>

          <template v-if="showSuccess">
            <h1 class="text-h4 font-weight-bold mb-3" :class="titleClass">Verifique seu e-mail</h1>
            <p class="text-body-1 mb-0" :class="subtitleClass">{{ successMessage }}</p>
            <v-btn
              block
              large
              depressed
              rounded
              color="primary"
              class="font-weight-bold text-none mt-8"
              :href="loginUrl"
              tag="a"
            >
              Voltar ao login
            </v-btn>
          </template>

          <template v-else>
            <h1 class="text-h4 font-weight-bold mb-2" :class="titleClass">Esqueci minha senha</h1>
            <p class="text-body-2 mb-6" :class="subtitleClass">
              Informe seu e-mail cadastrado. Se existir uma conta, enviaremos um link para redefinir a senha.
            </p>

            <v-form ref="form" v-model="formValid" class="cms-auth-fields" @submit.prevent="submitForgot">
              <v-text-field
                ref="firstField"
                v-model="email"
                outlined
                dense
                hide-details="auto"
                type="email"
                autocomplete="email"
                prepend-inner-icon="mdi-email-outline"
                :rules="emailRules"
                :error-messages="serverErrors.email"
                label="E-mail"
                class="cms-auth-input mb-6"
                :dark="dark"
                :color="fieldColor"
                @input="clearServerError('email')"
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
                Enviar link de recuperação
              </v-btn>
            </v-form>
          </template>

          <div v-if="!showSuccess" class="text-center mt-10">
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
  name: 'CmsForgotPassword',
  props: {
    csrfToken: { type: String, required: true },
    forgotSubmitUrl: { type: String, required: true },
    loginUrl: { type: String, required: true },
    logoUrl: { type: String, required: true },
    appName: { type: String, default: 'DinDin' },
    initialStatus: { type: String, default: '' },
    initialErrors: { type: Object, default: () => ({}) },
    oldInput: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      dark: false,
      formValid: true,
      email: '',
      submitting: false,
      serverErrors: {},
      emailRules: [
        (v) => (v != null && String(v).trim() !== '') || 'Informe o e-mail',
        (v) => /.+@.+\..+/.test(String(v || '').trim()) || 'E-mail inválido',
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
    showSuccess() {
      return Boolean(this.initialStatus && String(this.initialStatus).trim())
    },
    successMessage() {
      return this.initialStatus || 'Enviamos um link para seu e-mail.'
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
    if (this.oldInput.email) this.email = String(this.oldInput.email)
    if (!this.showSuccess) {
      this.$nextTick(() => {
        const c = this.$refs.firstField
        const el = c && (c.$refs.input || c.$refs.input_)
        if (el) el.focus()
      })
    }
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
    submitForgot() {
      if (!this.$refs.form || !this.$refs.form.validate()) return
      this.submitting = true
      submitNativePost(this.forgotSubmitUrl, {
        _token: this.csrfToken,
        email: String(this.email || '').trim(),
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
