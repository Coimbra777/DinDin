<template>
  <v-app class="admin-v-app">
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" bottom timeout="4000">
      {{ snackbar.text }}
      <template slot="action" slot-scope="{ attrs }">
        <v-btn text v-bind="attrs" @click="snackbar.show = false">OK</v-btn>
      </template>
    </v-snackbar>

    <v-app-bar app flat color="surface" :dark="$vuetify.theme.dark" elevation="0" class="admin-app-bar">
      <v-btn
        text
        color="primary"
        class="mr-1 text-none px-2"
        :href="financePanelUrl"
        aria-label="Voltar ao painel de finanças"
      >
        <v-icon left small>mdi-arrow-left</v-icon>
        <span class="d-none d-sm-inline">Voltar às finanças</span>
      </v-btn>
      <v-toolbar-title class="font-weight-black text-h6 pl-0">Administração</v-toolbar-title>
      <v-spacer />
      <span
        v-if="userName"
        class="text-caption finance-text-muted mr-2 d-none d-sm-inline text-truncate"
        style="max-width: 160px"
      >
        {{ userName }}
      </span>
      <v-btn text small color="secondary" class="mr-1 text-none" href="/cms/admin/users">
        <v-icon left small>mdi-view-dashboard-outline</v-icon>
        <span class="d-none d-sm-inline">Versão clássica</span>
      </v-btn>
      <finance-theme-toggle />
      <v-btn icon aria-label="Sair" @click="logout">
        <v-icon>mdi-logout-variant</v-icon>
      </v-btn>
    </v-app-bar>

    <v-main class="admin-main">
      <v-container fluid class="pa-4 pa-md-6">
        <user-list
          v-if="view === 'list'"
          :api-base="apiBase"
          @edit="openEdit"
          @error="showError"
        />
        <user-edit-modules
          v-else
          :api-base="apiBase"
          :user-id="editUserId"
          @back="view = 'list'"
          @saved="onSaved"
          @error="showError"
        />
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
import FinanceThemeToggle from '../finance/components/FinanceThemeToggle.vue'
import UserList from './components/UserList.vue'
import UserEditModules from './components/UserEditModules.vue'

export default {
  name: 'AdminApp',
  components: {
    FinanceThemeToggle,
    UserList,
    UserEditModules,
  },
  props: {
    apiBase: { type: String, default: '' },
    userName: { type: String, default: '' },
    financePanelUrl: { type: String, default: '/cms/finance/finance_dashboard' },
  },
  data() {
    return {
      view: 'list',
      editUserId: null,
      snackbar: { show: false, text: '', color: 'success' },
    }
  },
  methods: {
    openEdit(userId) {
      this.editUserId = userId
      this.view = 'edit'
    },
    onSaved(msg) {
      this.snackbar = { show: true, text: msg || 'Guardado com sucesso.', color: 'success' }
      this.view = 'list'
    },
    showError(message) {
      this.snackbar = { show: true, text: message || 'Ocorreu um erro.', color: 'error' }
    },
    logout() {
      const f = document.getElementById('admin-logout-form')
      if (f) f.submit()
    },
  },
}
</script>

<style scoped>
.admin-main {
  min-height: 100vh;
}
</style>
