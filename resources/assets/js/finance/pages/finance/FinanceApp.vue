<template>
  <v-app class="finance-v-app">
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" bottom timeout="3800">
      {{ snackbar.text }}
      <template slot="action" slot-scope="{ attrs }">
        <v-btn text v-bind="attrs" @click="snackbar.show = false">OK</v-btn>
      </template>
    </v-snackbar>

    <v-navigation-drawer
      v-model="navDrawer"
      app
      :dark="$vuetify.theme.dark"
      :permanent="$vuetify.breakpoint.mdAndUp"
      :temporary="$vuetify.breakpoint.smAndDown"
      width="260"
      color="surface"
      class="finance-drawer"
    >
      <div class="finance-drawer__brand pa-4">
        <div class="text-subtitle-1 font-weight-black finance-drawer__title">Finanças</div>
        <div class="text-caption finance-text-muted">Menu</div>
      </div>
      <v-divider />
      <v-list nav dense class="py-3 px-2">
        <v-list-item
          v-for="item in navItems"
          :key="item.value"
          link
          rounded
          :class="{ 'primary white--text': view === item.value }"
          @click="goView(item.value)"
        >
          <v-list-item-icon class="mr-3">
            <v-icon :color="view === item.value ? 'white' : 'secondary'">{{ item.icon }}</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title
              :class="view === item.value ? 'white--text font-weight-medium' : 'secondary--text'"
            >
              {{ item.title }}
            </v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar app :dark="$vuetify.theme.dark" flat color="surface" elevation="0" class="finance-app-bar">
      <v-app-bar-nav-icon
        v-if="$vuetify.breakpoint.smAndDown"
        class="mr-1"
        aria-label="Abrir menu"
        @click.stop="navDrawer = !navDrawer"
      />
      <v-toolbar-title class="font-weight-black finance-app-bar__title text-h6">
        Minhas finanças
      </v-toolbar-title>
      <v-spacer />
      <span v-if="userName" class="text-caption finance-text-muted mr-2 d-none d-sm-inline text-truncate" style="max-width: 140px">
        {{ userName }}
      </span>
      <finance-theme-toggle />
      <v-btn icon aria-label="Sair" @click="logout">
        <v-icon>mdi-logout-variant</v-icon>
      </v-btn>
    </v-app-bar>

    <v-main class="finance-main">
      <v-container fluid class="pa-3 pa-md-6 pb-20">
        <v-row v-if="showMonthSelector" align="center" class="mb-3">
          <v-col cols="12" md="4">
            <v-select
              v-model="month"
              :items="monthItems"
              item-text="text"
              item-value="value"
              label="Mês"
              outlined
              dense
              :dark="$vuetify.theme.dark"
              hide-details="auto"
              prepend-inner-icon="mdi-calendar-month"
              @change="onMonthChange"
            />
          </v-col>
        </v-row>

        <v-fade-transition mode="out-in">
          <!-- DASHBOARD (GET /api/dashboard + Chart.js) -->
          <div v-if="view === 'dashboard'" key="dash">
            <dashboard
              :month="month"
              :api-base="apiBase"
              :refresh-key="dashboardRefreshKey"
              @error="showError"
            />
          </div>

          <!-- TRANSAÇÕES -->
          <div v-else-if="view === 'transactions'" key="tx">
            <v-row dense class="mb-4">
              <v-col cols="12" md="5">
                <v-select
                  v-model="filterCategoryId"
                  :items="categoryFilterItems"
                  label="Categoria"
                  outlined
                  dense
                  :dark="$vuetify.theme.dark"
                  clearable
                  hide-details="auto"
                  prepend-inner-icon="mdi-filter-variant"
                  @change="loadTransactions"
                />
              </v-col>
            </v-row>
            <transaction-list
              title="Movimentações"
              :items="transactions"
              :loading="loading.transactions"
              @edit="openEdit"
              @delete="askDelete"
            />
          </div>

          <!-- CATEGORIAS -->
          <div v-else-if="view === 'categories'" key="cat">
            <v-card class="rounded-xl" elevation="1">
              <v-card-title class="subtitle-1 font-weight-bold py-4">
                <v-icon left color="primary">mdi-shape-outline</v-icon>
                Suas categorias
                <v-spacer />
                <v-btn color="primary" depressed rounded @click="openCategoryCreate">
                  <v-icon left small>mdi-plus</v-icon>
                  Nova
                </v-btn>
              </v-card-title>
              <v-divider />
              <v-card-text v-if="loading.categories" class="text-center py-8">
                <v-progress-circular indeterminate color="primary" />
              </v-card-text>
              <v-card-text v-else-if="categories.length === 0" class="text-center finance-text-muted py-10">
                Nenhuma categoria. Crie a primeira para organizar suas transações.
              </v-card-text>
              <v-list v-else two-line class="py-0">
                <template v-for="(c, i) in categories">
                  <v-list-item :key="c.id" :class="{ 'finance-cat-row--alt': i % 2 === 0 }">
                    <v-list-item-avatar>
                      <v-avatar :color="chipBg(c)" size="40">
                        <v-icon dark small>mdi-tag</v-icon>
                      </v-avatar>
                    </v-list-item-avatar>
                    <v-list-item-content>
                      <v-list-item-title class="font-weight-medium">{{ c.name }}</v-list-item-title>
                      <v-list-item-subtitle v-if="c.color">{{ c.color }}</v-list-item-subtitle>
                    </v-list-item-content>
                    <v-list-item-action class="flex-row mx-0">
                      <v-btn icon small @click="openCategoryEdit(c)"><v-icon small color="secondary">mdi-pencil</v-icon></v-btn>
                      <v-btn icon small color="error" @click="askDeleteCategory(c)"><v-icon small>mdi-delete-outline</v-icon></v-btn>
                    </v-list-item-action>
                  </v-list-item>
                  <v-divider v-if="i < categories.length - 1" :key="'d' + c.id" />
                </template>
              </v-list>
            </v-card>
          </div>

          <!-- CARTÕES -->
          <div v-else-if="view === 'cards'" key="cards">
            <credit-cards-page
              :api-base="apiBase"
              :refresh-key="creditCardsRefreshKey"
              @saved="onCreditCardPanelSaved"
              @error="showError"
            />
          </div>

          <!-- PROJEÇÃO -->
          <div v-else-if="view === 'projection'" key="proj">
            <projection-page
              :api-base="apiBase"
              :refresh-key="projectionRefreshKey"
              @error="showError"
            />
          </div>

          <!-- RELATÓRIOS -->
          <div v-else-if="view === 'reports'" key="rep">
            <reports-page
              :api-base="apiBase"
              :month="month"
              :refresh-key="reportsRefreshKey"
              @error="showError"
            />
          </div>

          <!-- ALERTAS -->
          <div v-else-if="view === 'alerts'" key="alerts">
            <alerts-page
              :api-base="apiBase"
              :month="month"
              :refresh-key="alertsRefreshKey"
              @error="showError"
            />
          </div>

          <!-- INSIGHTS -->
          <div v-else-if="view === 'insights'" key="insights">
            <insights-page
              :api-base="apiBase"
              :month="month"
              :refresh-key="insightsRefreshKey"
              @error="showError"
            />
          </div>

          <!-- SIMULADOR CARTÃO -->
          <div v-else-if="view === 'simulator'" key="sim">
            <credit-simulator-page
              :api-base="apiBase"
              :refresh-key="simulatorRefreshKey"
              @error="showError"
            />
          </div>

          <!-- PLANEJAMENTO -->
          <div v-else-if="view === 'planning'" key="plan">
            <planning-page
              :api-base="apiBase"
              :refresh-key="planningRefreshKey"
              @error="showError"
            />
          </div>
        </v-fade-transition>
      </v-container>
    </v-main>

    <v-tooltip v-if="showTransactionFab" left>
      <template slot="activator" slot-scope="{ on, attrs }">
        <v-btn
          fab
          fixed
          bottom
          right
          large
          color="primary"
          dark
          class="finance-fab finance-fab--primary-action"
          elevation="10"
          v-bind="attrs"
          v-on="on"
          @click="openCreate"
        >
          <v-icon size="28">mdi-plus</v-icon>
        </v-btn>
      </template>
      <span>Nova transação</span>
    </v-tooltip>

    <v-tooltip v-if="view === 'categories'" left>
      <template slot="activator" slot-scope="{ on, attrs }">
        <v-btn
          fab
          fixed
          bottom
          right
          color="secondary"
          dark
          class="finance-fab finance-fab--secondary-action"
          elevation="10"
          v-bind="attrs"
          v-on="on"
          @click="openCategoryCreate"
        >
          <v-icon>mdi-plus</v-icon>
        </v-btn>
      </template>
      <span>Nova categoria</span>
    </v-tooltip>

    <transaction-form
      v-model="formOpen"
      :api-base="apiBase"
      :categories="categories"
      :credit-cards="creditCards"
      :transaction="editTransaction"
      @saved="onSaved"
      @error="showError"
    />

    <category-form-dialog
      v-model="categoryDialog"
      :api-base="apiBase"
      :category="editCategory"
      @saved="onCategorySaved"
      @error="showError"
    />

    <v-dialog v-model="deleteDialog.open" max-width="400" persistent>
      <v-card class="rounded-lg">
        <v-card-title class="headline">Excluir transação?</v-card-title>
        <v-card-text class="finance-text-muted">Esta ação não pode ser desfeita.</v-card-text>
        <v-card-actions>
          <v-btn text color="secondary" @click="deleteDialog.open = false">Cancelar</v-btn>
          <v-spacer />
          <v-btn color="error" depressed :loading="deleteDialog.loading" @click="confirmDelete">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteCatDialog.open" max-width="400" persistent>
      <v-card class="rounded-lg">
        <v-card-title class="headline">Excluir categoria?</v-card-title>
        <v-card-text class="finance-text-muted">Transações associadas ficam sem categoria.</v-card-text>
        <v-card-actions>
          <v-btn text color="secondary" @click="deleteCatDialog.open = false">Cancelar</v-btn>
          <v-spacer />
          <v-btn color="error" depressed :loading="deleteCatDialog.loading" @click="confirmDeleteCategory">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script>
import axios from 'axios'
import Dashboard from '../../components/Dashboard.vue'
import TransactionList from '../../components/TransactionList.vue'
import TransactionForm from '../../components/TransactionForm.vue'
import CategoryFormDialog from '../../components/CategoryFormDialog.vue'
import CreditCardsPage from '../cards/CreditCardsPage.vue'
import ReportsPage from '../reports/ReportsPage.vue'
import ProjectionPage from '../projection/ProjectionPage.vue'
import AlertsPage from '../alerts/AlertsPage.vue'
import InsightsPage from '../insights/InsightsPage.vue'
import CreditSimulatorPage from '../simulator/CreditSimulatorPage.vue'
import PlanningPage from '../planning/PlanningPage.vue'
import FinanceThemeToggle from '../../components/FinanceThemeToggle.vue'
import { monthChoices, normalizeMonth } from '../../format'
import { applyBodyThemeClass, getStoredTheme } from '../../financeTheme'

const VALID_VIEWS = [
  'dashboard',
  'transactions',
  'categories',
  'cards',
  'projection',
  'reports',
  'alerts',
  'insights',
  'simulator',
  'planning',
]

export default {
  name: 'FinanceApp',
  components: {
    Dashboard,
    TransactionList,
    TransactionForm,
    CategoryFormDialog,
    CreditCardsPage,
    ReportsPage,
    ProjectionPage,
    AlertsPage,
    InsightsPage,
    CreditSimulatorPage,
    PlanningPage,
    FinanceThemeToggle,
  },
  props: {
    initialView: { type: String, default: 'dashboard' },
    initialMonth: { type: String, default: '' },
    apiBase: { type: String, required: true },
    userName: { type: String, default: '' },
  },
  data() {
    const v = VALID_VIEWS.includes(this.initialView) ? this.initialView : 'dashboard'
    return {
      navDrawer: false,
      view: v,
      month: normalizeMonth(this.initialMonth),
      monthItems: monthChoices(24),
      dashboardRefreshKey: 0,
      creditCardsRefreshKey: 0,
      reportsRefreshKey: 0,
      projectionRefreshKey: 0,
      alertsRefreshKey: 0,
      insightsRefreshKey: 0,
      simulatorRefreshKey: 0,
      planningRefreshKey: 0,
      navItems: [
        { title: 'Dashboard', value: 'dashboard', icon: 'mdi-view-dashboard-outline' },
        { title: 'Transações', value: 'transactions', icon: 'mdi-bank-transfer' },
        { title: 'Categorias', value: 'categories', icon: 'mdi-shape-outline' },
        { title: 'Cartões', value: 'cards', icon: 'mdi-credit-card-outline' },
        { title: 'Projeção', value: 'projection', icon: 'mdi-chart-timeline-variant' },
        { title: 'Relatórios', value: 'reports', icon: 'mdi-file-chart-outline' },
        { title: 'Alertas', value: 'alerts', icon: 'mdi-bell-alert-outline' },
        { title: 'Insights', value: 'insights', icon: 'mdi-lightbulb-outline' },
        { title: 'Simulador', value: 'simulator', icon: 'mdi-calculator-variant' },
        { title: 'Planejamento', value: 'planning', icon: 'mdi-calendar-check' },
      ],
      transactions: [],
      categories: [],
      creditCards: [],
      filterCategoryId: null,
      loading: {
        transactions: false,
        categories: false,
      },
      snackbar: { show: false, text: '', color: 'primary' },
      formOpen: false,
      editTransaction: null,
      deleteDialog: { open: false, loading: false, item: null },
      categoryDialog: false,
      editCategory: null,
      deleteCatDialog: { open: false, loading: false, item: null },
    }
  },
  computed: {
    categoryFilterItems() {
      return this.categories.map((c) => ({ text: c.name, value: c.id }))
    },
    showMonthSelector() {
      return ['dashboard', 'transactions', 'reports', 'alerts', 'insights'].includes(this.view)
    },
    showTransactionFab() {
      return ['dashboard', 'transactions'].includes(this.view)
    },
  },
  watch: {
    view(v) {
      if (v === 'categories') this.loadCategories()
      if (v === 'cards') {
        this.creditCardsRefreshKey += 1
        this.loadCreditCards()
      }
      if (v === 'reports') {
        this.reportsRefreshKey += 1
      }
      if (v === 'projection') {
        this.projectionRefreshKey += 1
      }
      if (v === 'alerts') {
        this.alertsRefreshKey += 1
      }
      if (v === 'insights') {
        this.insightsRefreshKey += 1
      }
      if (v === 'simulator') {
        this.simulatorRefreshKey += 1
      }
      if (v === 'planning') {
        this.planningRefreshKey += 1
      }
    },
  },
  created() {
    applyBodyThemeClass(this.$vuetify.theme.dark)
    if (typeof window !== 'undefined' && window.matchMedia && getStoredTheme() === null) {
      this._schemeMq = window.matchMedia('(prefers-color-scheme: dark)')
      this._onSchemeChange = () => {
        if (getStoredTheme() !== null) return
        this.$vuetify.theme.dark = this._schemeMq.matches
        applyBodyThemeClass(this.$vuetify.theme.dark)
      }
      this._schemeMq.addEventListener('change', this._onSchemeChange)
    }
  },
  beforeDestroy() {
    if (this._schemeMq && this._onSchemeChange) {
      this._schemeMq.removeEventListener('change', this._onSchemeChange)
    }
  },
  mounted() {
    this.refreshAll()
  },
  methods: {
    logout() {
      const f = document.getElementById('finance-logout-form')
      if (f) f.submit()
    },
    chipBg(c) {
      if (c.color && /^#[0-9A-Fa-f]{6}$/.test(c.color)) return c.color
      return 'secondary'
    },
    async refreshAll() {
      this.dashboardRefreshKey += 1
      this.creditCardsRefreshKey += 1
      this.reportsRefreshKey += 1
      this.projectionRefreshKey += 1
      this.alertsRefreshKey += 1
      this.insightsRefreshKey += 1
      this.simulatorRefreshKey += 1
      this.planningRefreshKey += 1
      await Promise.all([this.loadTransactions(), this.loadCategories(), this.loadCreditCards()])
    },
    goView(value) {
      this.view = value
      if (this.$vuetify.breakpoint.smAndDown) {
        this.navDrawer = false
      }
    },
    /** Troca de mês: lista de transações + Dashboard observa `month` (sem segundo fetch por key). */
    async onMonthChange() {
      await this.loadTransactions()
    },
    async loadTransactions() {
      this.loading.transactions = true
      try {
        const params = { month: this.month }
        if (this.filterCategoryId) params.category_id = this.filterCategoryId
        const { data } = await axios.get(`${this.apiBase}/transactions`, { params })
        this.transactions = data.data || []
      } catch (e) {
        this.transactions = []
      } finally {
        this.loading.transactions = false
      }
    },
    async loadCategories() {
      this.loading.categories = true
      try {
        const { data } = await axios.get(`${this.apiBase}/categories`)
        this.categories = data.data || []
      } catch (e) {
        this.categories = []
      } finally {
        this.loading.categories = false
      }
    },
    async loadCreditCards() {
      try {
        const { data } = await axios.get(`${this.apiBase}/credit-cards`)
        this.creditCards = data.data || []
      } catch (e) {
        this.creditCards = []
      }
    },
    onCreditCardPanelSaved() {
      this.loadCreditCards()
      this.dashboardRefreshKey += 1
    },
    openCreate() {
      this.editTransaction = null
      this.formOpen = true
    },
    openEdit(item) {
      this.editTransaction = { ...item }
      this.formOpen = true
    },
    askDelete(item) {
      this.deleteDialog.item = item
      this.deleteDialog.open = true
    },
    async confirmDelete() {
      if (!this.deleteDialog.item) return
      this.deleteDialog.loading = true
      try {
        await axios.delete(`${this.apiBase}/transactions/${this.deleteDialog.item.id}`)
        this.deleteDialog.open = false
        this.toast('Transação removida.', 'success')
        this.refreshAll()
      } catch (e) {
        this.showError('Não foi possível excluir.')
      } finally {
        this.deleteDialog.loading = false
        this.deleteDialog.item = null
      }
    },
    openCategoryCreate() {
      this.editCategory = null
      this.categoryDialog = true
    },
    openCategoryEdit(c) {
      this.editCategory = { ...c }
      this.categoryDialog = true
    },
    askDeleteCategory(c) {
      this.deleteCatDialog.item = c
      this.deleteCatDialog.open = true
    },
    async confirmDeleteCategory() {
      if (!this.deleteCatDialog.item) return
      this.deleteCatDialog.loading = true
      try {
        await axios.delete(`${this.apiBase}/categories/${this.deleteCatDialog.item.id}`)
        this.deleteCatDialog.open = false
        this.toast('Categoria removida.', 'success')
        await this.loadCategories()
        this.refreshAll()
      } catch (e) {
        this.showError('Não foi possível excluir a categoria.')
      } finally {
        this.deleteCatDialog.loading = false
        this.deleteCatDialog.item = null
      }
    },
    onCategorySaved() {
      this.toast('Categoria guardada.', 'success')
      this.editCategory = null
      this.loadCategories().then(() => this.refreshAll())
    },
    onSaved() {
      this.toast(this.editTransaction ? 'Transação atualizada.' : 'Transação criada.', 'success')
      this.editTransaction = null
      this.refreshAll()
    },
    toast(text, color = 'primary') {
      this.snackbar = { show: true, text, color }
    },
    showError(msg) {
      this.snackbar = { show: true, text: msg, color: 'error' }
    },
  },
}
</script>

<style scoped>
.finance-v-app {
  min-height: 100vh !important;
}
.finance-fab {
  bottom: 24px !important;
  right: 24px !important;
  z-index: 24;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease !important;
}
.finance-fab--primary-action:hover {
  transform: scale(1.04);
  box-shadow: 0 8px 28px rgba(255, 0, 0, 0.38) !important;
}
.finance-fab--secondary-action:hover {
  transform: scale(1.04);
  box-shadow: 0 8px 26px rgba(133, 136, 143, 0.4) !important;
}
</style>
