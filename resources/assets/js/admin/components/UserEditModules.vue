<template>
  <div class="d-flex justify-center">
    <v-card class="rounded-lg admin-edit-card" elevation="1" :dark="$vuetify.theme.dark" width="100%">
    <v-card-title class="subtitle-1 font-weight-bold py-4">
      <v-btn icon class="mr-2" aria-label="Voltar" @click="$emit('back')">
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      Módulos do utilizador
    </v-card-title>
    <v-divider />

    <v-card-text v-if="loadError" class="py-8">
      <v-alert type="error" outlined dense>{{ loadError }}</v-alert>
      <v-btn text color="primary" @click="$emit('back')">Voltar à lista</v-btn>
    </v-card-text>

    <template v-else>
      <v-card-text v-if="loading" class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="48" />
        <div class="text-caption finance-text-muted mt-3">A carregar…</div>
      </v-card-text>

      <template v-else>
        <v-card-text class="pt-6">
          <div class="text-subtitle-2 font-weight-medium mb-1">{{ displayName }}</div>
          <div class="text-body-2 finance-text-muted mb-6">{{ displayEmail }}</div>

          <v-switch
            v-model="isAdmin"
            inset
            color="primary"
            class="mt-0"
            label="Administrador"
            hide-details="auto"
          />

          <div class="text-subtitle-2 font-weight-medium mt-6 mb-2">Módulos extra</div>
          <p class="text-caption finance-text-muted mb-2">
            O acesso base (dashboard, transações, categorias, alertas, insights, simulador) é automático para todos.
            Marque abaixo apenas os módulos adicionais permitidos.
          </p>
          <v-list dense class="pa-0 transparent" :dark="$vuetify.theme.dark">
            <v-list-item v-for="m in allModules" :key="m.slug" class="px-0">
              <v-list-item-action class="mr-3 my-0">
                <v-checkbox
                  v-model="selectedModules"
                  :value="m.slug"
                  color="primary"
                  hide-details
                  dense
                />
              </v-list-item-action>
              <v-list-item-content>
                <v-list-item-title>{{ m.name }}</v-list-item-title>
                <v-list-item-subtitle class="text-caption">{{ m.slug }}</v-list-item-subtitle>
              </v-list-item-content>
            </v-list-item>
          </v-list>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-4">
          <v-btn text rounded @click="$emit('back')">Cancelar</v-btn>
          <v-spacer />
          <v-btn
            color="primary"
            depressed
            rounded
            :loading="saving"
            :disabled="loading"
            @click="save"
          >
            <v-icon left small>mdi-content-save-outline</v-icon>
            Guardar
          </v-btn>
        </v-card-actions>
      </template>
    </template>
    </v-card>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'UserEditModules',
  props: {
    apiBase: { type: String, required: true },
    userId: { type: Number, required: true },
  },
  data() {
    return {
      loading: true,
      saving: false,
      loadError: null,
      displayName: '',
      displayEmail: '',
      isAdmin: false,
      allModules: [],
      selectedModules: [],
      /** Evita loop quando o switch de admin altera os checkboxes. */
      _fromAdminSwitch: false,
    }
  },
  watch: {
    userId: {
      immediate: true,
      handler() {
        this.fetchUser()
      },
    },
    isAdmin(val) {
      if (this.loading) return
      this._fromAdminSwitch = true
      if (val) {
        this.selectedModules = this.allModules.map((m) => m.slug)
      } else {
        this.selectedModules = []
      }
      this.$nextTick(() => {
        this._fromAdminSwitch = false
      })
    },
    selectedModules: {
      deep: true,
      handler(modules) {
        if (this.loading || this._fromAdminSwitch) return
        const slugs = this.allModules.map((m) => m.slug)
        if (slugs.length === 0) return
        const allOn = slugs.every((s) => modules.includes(s))
        if (allOn && !this.isAdmin) {
          this.isAdmin = true
          return
        }
        if (this.isAdmin && !allOn) {
          this.isAdmin = false
        }
      },
    },
  },
  methods: {
    async fetchUser() {
      this.loading = true
      this.loadError = null
      this.selectedModules = []
      try {
        const { data } = await axios.get(`${this.apiBase}/users/${this.userId}`)
        const u = data.user
        this.displayName = u.name
        this.displayEmail = u.email
        this.allModules = data.all_modules || []
        this.isAdmin = !!u.is_admin
        const fromApi = Array.isArray(u.modules) ? u.modules : []
        if (this.isAdmin) {
          this.selectedModules = this.allModules.map((m) => m.slug)
        } else {
          this.selectedModules = fromApi.slice()
        }
      } catch (e) {
        this.loadError =
          (e.response && e.response.data && e.response.data.message) ||
          'Não foi possível carregar o utilizador.'
      } finally {
        this.loading = false
      }
    },
    async save() {
      this.saving = true
      try {
        const { data } = await axios.post(`${this.apiBase}/users/${this.userId}/modules`, {
          is_admin: this.isAdmin,
          modules: this.selectedModules,
        })
        if (data.user) {
          this._fromAdminSwitch = true
          this.isAdmin = !!data.user.is_admin
          if (this.isAdmin) {
            this.selectedModules = this.allModules.map((m) => m.slug)
          } else if (Array.isArray(data.user.modules)) {
            this.selectedModules = data.user.modules.slice()
          }
          this.$nextTick(() => {
            this._fromAdminSwitch = false
          })
        }
        this.$emit('saved', (data && data.message) || 'Guardado com sucesso.')
      } catch (e) {
        let msg = 'Não foi possível guardar.'
        const d = e.response && e.response.data
        if (d) {
          if (d.errors) {
            const first = Object.values(d.errors)[0]
            if (Array.isArray(first) && first[0]) msg = first[0]
          } else if (d.message) msg = d.message
        }
        this.$emit('error', msg)
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<style scoped>
.admin-edit-card {
  max-width: 640px;
}
</style>
