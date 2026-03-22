<template>
  <v-app class="finance-v-app">
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      bottom
      timeout="3800"
    >
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
        <div class="text-subtitle-1 font-weight-black finance-drawer__title">
          Finanças
        </div>
        <div class="text-caption finance-text-muted">Menu</div>
      </div>
      <v-divider />
      <v-list nav dense class="py-3 px-2">
        <v-list-item
          v-for="item in navItems"
          :key="item.value"
          link
          rounded
          :data-tour="
            item.value === 'categories' ? 'nav-categories' : undefined
          "
          :class="{ 'primary white--text': view === item.value }"
          @click="goView(item.value)"
        >
          <v-list-item-icon class="mr-3">
            <v-icon :color="view === item.value ? 'white' : 'secondary'">{{
              item.icon
            }}</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title
              :class="
                view === item.value
                  ? 'white--text font-weight-medium'
                  : 'secondary--text'
              "
            >
              {{ item.title }}
            </v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <template v-if="isAdmin">
          <v-divider class="my-2" />
          <v-list-item href="/cms/admin" link rounded>
            <v-list-item-icon class="mr-3">
              <v-icon color="secondary">mdi-shield-account-outline</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title class="secondary--text font-weight-medium"
                >Administração</v-list-item-title
              >
            </v-list-item-content>
          </v-list-item>
        </template>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar
      app
      :dark="$vuetify.theme.dark"
      flat
      color="surface"
      elevation="0"
      class="finance-app-bar"
    >
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
      <span
        v-if="userName"
        class="text-caption finance-text-muted mr-2 d-none d-sm-inline text-truncate"
        style="max-width: 140px"
      >
        {{ userName }}
      </span>
      <v-btn
        text
        small
        color="secondary"
        class="mr-1 finance-help-toolbar-btn"
        aria-label="Como funciona?"
        @click="helpDialog = true"
      >
        <v-icon left small>mdi-help-circle-outline</v-icon>
        <span class="d-none d-sm-inline text-none">Como funciona?</span>
      </v-btn>
      <finance-theme-toggle />
      <v-btn icon aria-label="Sair" @click="logout">
        <v-icon>mdi-logout-variant</v-icon>
      </v-btn>
    </v-app-bar>

    <v-main class="finance-main">
      <v-container fluid class="pa-3 pa-md-6 pb-20">
        <v-row v-if="showMonthSelector" class="mb-4 finance-month-row">
          <v-col cols="12">
            <v-sheet
              outlined
              rounded
              class="finance-month-bar pa-3 pa-sm-4 d-flex flex-column flex-md-row align-md-center flex-wrap"
            >
              <div
                class="finance-month-bar__meta mr-md-6 mb-3 mb-md-0 flex-shrink-0"
              >
                <div class="d-flex align-center mb-1">
                  <v-icon color="primary" class="mr-2" size="22"
                    >mdi-calendar-month</v-icon
                  >
                  <span
                    class="text-overline secondary--text text-uppercase letter-wider font-weight-bold"
                  >
                    Período
                  </span>
                </div>
                <div
                  class="finance-month-bar__hint text-caption finance-text-muted"
                >
                  Escolha o mês dos dados (dashboard, movimentações e
                  relatórios).
                </div>
              </div>

              <div
                class="finance-month-bar__controls d-flex align-center flex-grow-1"
                style="gap: 10px"
              >
                <v-tooltip bottom>
                  <template #activator="{ on, attrs }">
                    <v-btn
                      icon
                      outlined
                      small
                      type="button"
                      class="flex-shrink-0"
                      aria-label="Ver mês anterior"
                      :disabled="!canShiftMonthOlder"
                      v-bind="attrs"
                      v-on="on"
                      @click="shiftMonthOlder"
                    >
                      <v-icon>mdi-chevron-left</v-icon>
                    </v-btn>
                  </template>
                  <span>Mês anterior</span>
                </v-tooltip>

                <v-select
                  v-model="month"
                  class="finance-month-select flex-grow-1"
                  :items="monthItems"
                  item-text="text"
                  item-value="value"
                  label="Mês de referência"
                  outlined
                  dense
                  hide-details
                  :menu-props="{ offsetY: true, maxHeight: 320 }"
                  aria-label="Selecionar mês de referência"
                  @change="onMonthChange"
                >
                  <template #item="{ item, on, attrs }">
                    <v-list-item v-bind="attrs" v-on="on">
                      <v-list-item-content>
                        <v-list-item-title
                          class="text-body-2 font-weight-medium"
                        >
                          {{ item.text }}
                        </v-list-item-title>
                        <v-list-item-subtitle class="text-caption">
                          {{ item.short }}
                        </v-list-item-subtitle>
                      </v-list-item-content>
                    </v-list-item>
                  </template>
                  <template #selection="{ item }">
                    <span
                      v-if="item && item.text"
                      class="d-flex flex-column align-start py-1 finance-month-select__selection"
                    >
                      <span
                        class="text-body-2 font-weight-medium text-truncate"
                        style="max-width: 100%"
                      >
                        {{ item.text }}
                      </span>
                      <span class="text-caption finance-text-muted">{{
                        item.short
                      }}</span>
                    </span>
                    <span v-else class="text-body-2">{{ monthLabelPt }}</span>
                  </template>
                </v-select>

                <v-tooltip bottom>
                  <template #activator="{ on, attrs }">
                    <v-btn
                      icon
                      outlined
                      small
                      type="button"
                      class="flex-shrink-0"
                      aria-label="Ver mês seguinte"
                      :disabled="!canShiftMonthNewer"
                      v-bind="attrs"
                      v-on="on"
                      @click="shiftMonthNewer"
                    >
                      <v-icon>mdi-chevron-right</v-icon>
                    </v-btn>
                  </template>
                  <span>Mês seguinte</span>
                </v-tooltip>
              </div>
            </v-sheet>
          </v-col>
        </v-row>

        <v-fade-transition mode="out-in">
          <div v-if="!canShowView(view)" key="denied" class="text-center py-12">
            <p class="finance-text-muted">Sem permissão para esta área.</p>
            <v-btn
              text
              color="primary"
              class="mt-2"
              @click="recoverToFirstAllowedView"
            >
              Ir para {{ firstAllowedNavTitle() }}
            </v-btn>
          </div>
          <!-- DASHBOARD (GET /cms/finance/api/dashboard + Chart.js) -->
          <div v-else-if="view === 'dashboard'" key="dash">
            <dashboard
              :month="month"
              :api-base="apiBase"
              :refresh-key="dashboardRefreshKey"
              @error="showError"
            />
          </div>

          <!-- MOVIMENTAÇÕES -->
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
            <v-row
              v-if="txSummary"
              dense
              class="mb-4 tx-summary-cards-row"
              align="stretch"
            >
              <v-col cols="12" sm="4" class="d-flex">
                <v-card
                  class="rounded-xl tx-summary-card flex-grow-1 d-flex flex-column"
                  flat
                  outlined
                >
                  <v-card-text class="py-3 px-4 flex-grow-1 d-flex flex-column">
                    <div
                      class="text-overline secondary--text text-uppercase letter-wider"
                    >
                      Saldo acumulado
                    </div>
                    <div
                      class="text-h6 font-weight-bold tabular-nums"
                      :class="txSaldoAcumuladoClass"
                    >
                      {{ formatBRL(txSummary.saldo_acumulado_ate_mes) }}
                    </div>
                    <div class="text-caption secondary--text mt-auto">
                      Até {{ monthLabelPt }}
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" sm="4" class="d-flex">
                <v-card
                  class="rounded-xl tx-summary-card flex-grow-1 d-flex flex-column"
                  flat
                  outlined
                >
                  <v-card-text class="py-3 px-4 flex-grow-1 d-flex flex-column">
                    <div
                      class="text-overline secondary--text text-uppercase letter-wider"
                    >
                      Resultado do mês
                    </div>
                    <div
                      class="text-h6 font-weight-bold tabular-nums"
                      :class="txResultadoMesClass"
                    >
                      {{ formatBRL(txSummary.available_this_month) }}
                    </div>
                    <div class="text-caption secondary--text mt-auto">
                      Receitas − despesas
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" sm="4" class="d-flex">
                <v-card
                  class="rounded-xl tx-summary-card tx-summary-composicao flex-grow-1 d-flex flex-column"
                  flat
                  outlined
                >
                  <v-card-text class="py-3 px-4 flex-grow-1 d-flex flex-column">
                    <div
                      class="text-overline secondary--text text-uppercase letter-wider"
                    >
                      Saldo (como fecha)
                    </div>
                    <div class="text-caption secondary--text mb-2">
                      Acumulado dos meses anteriores + resultado deste mês
                    </div>
                    <div
                      class="d-flex justify-space-between align-baseline text-body-2 mb-1"
                    >
                      <span class="secondary--text pr-2"
                        >Até fim de {{ monthLabelPreviousPt }}</span
                      >
                      <span
                        class="tabular-nums font-weight-medium text-right"
                        >{{
                          formatBRL(txSummary.acumulado_ate_inicio_mes)
                        }}</span
                      >
                    </div>
                    <div
                      class="d-flex justify-space-between align-baseline text-body-2"
                    >
                      <span class="secondary--text pr-2"
                        >+ Este mês</span
                      >
                      <span
                        class="tabular-nums font-weight-medium text-right"
                        :class="txResultadoMesClass"
                        >{{ formatBRL(txSummary.available_this_month) }}</span
                      >
                    </div>
                    <div class="mt-auto pt-2">
                      <v-divider class="mb-2" />
                      <div class="d-flex justify-space-between align-center">
                        <span class="text-caption secondary--text"
                          >= Até {{ monthLabelPt }}</span
                        >
                        <span
                          class="text-h6 font-weight-bold tabular-nums"
                          :class="txSaldoAcumuladoClass"
                          >{{
                            formatBRL(txSummary.saldo_acumulado_ate_mes)
                          }}</span
                        >
                      </div>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
            <transaction-list
              title="Movimentações"
              :items="transactions"
              :loading="loading.transactions"
              @edit="openEdit"
              @delete="askDelete"
              @duplicate="openDuplicateDialog"
            />
            <div
              v-if="
                transactionsMeta &&
                transactions.length &&
                transactionsMeta.total > 0
              "
              class="text-center text-caption secondary--text mt-2 mb-1"
            >
              A mostrar {{ transactionsMeta.from }}–{{ transactionsMeta.to }} de
              {{ transactionsMeta.total }}
              movimentações
            </div>
            <div v-if="canLoadMoreTransactions" class="text-center mb-4">
              <v-btn
                outlined
                color="primary"
                class="text-none"
                :loading="loadingMoreTransactions"
                :disabled="loading.transactions"
                @click="loadMoreTransactions"
              >
                Carregar mais
              </v-btn>
            </div>
          </div>

          <!-- CATEGORIAS -->
          <div v-else-if="view === 'categories'" key="cat">
            <v-card class="rounded-xl" elevation="1">
              <v-card-title class="subtitle-1 font-weight-bold py-4">
                <v-icon left color="primary">mdi-shape-outline</v-icon>
                Suas categorias
                <v-spacer />
                <v-btn
                  color="primary"
                  depressed
                  rounded
                  @click="openCategoryCreate"
                >
                  <v-icon left small>mdi-plus</v-icon>
                  Nova
                </v-btn>
              </v-card-title>
              <v-divider />
              <v-card-text v-if="loading.categories" class="text-center py-8">
                <v-progress-circular indeterminate color="primary" />
              </v-card-text>
              <v-card-text
                v-else-if="categories.length === 0"
                class="text-center finance-text-muted py-10"
              >
                Nenhuma categoria. Crie a primeira para organizar suas
                transações.
              </v-card-text>
              <v-list v-else two-line class="py-0">
                <template v-for="(c, i) in categories">
                  <v-list-item
                    :key="c.id"
                    :class="{ 'finance-cat-row--alt': i % 2 === 0 }"
                  >
                    <v-list-item-avatar>
                      <v-avatar :color="chipBg(c)" size="40">
                        <v-icon dark small>mdi-tag</v-icon>
                      </v-avatar>
                    </v-list-item-avatar>
                    <v-list-item-content>
                      <v-list-item-title class="font-weight-medium">{{
                        c.name
                      }}</v-list-item-title>
                      <v-list-item-subtitle class="text-caption">
                        <span
                          :class="
                            c.type === 'income'
                              ? 'success--text'
                              : 'error--text'
                          "
                          >{{ categoryTypeLabel(c) }}</span
                        >
                        <span
                          v-if="c.type === 'expense' && c.group"
                          class="text--secondary"
                        >
                          · {{ c.group }}</span
                        >
                        <span v-if="c.color" class="text--secondary">
                          · {{ c.color }}</span
                        >
                      </v-list-item-subtitle>
                    </v-list-item-content>
                    <v-list-item-action class="flex-row mx-0">
                      <v-btn icon small @click="openCategoryEdit(c)"
                        ><v-icon small color="secondary"
                          >mdi-pencil</v-icon
                        ></v-btn
                      >
                      <v-btn
                        icon
                        small
                        color="error"
                        @click="askDeleteCategory(c)"
                        ><v-icon small>mdi-delete-outline</v-icon></v-btn
                      >
                    </v-list-item-action>
                  </v-list-item>
                  <v-divider
                    v-if="i < categories.length - 1"
                    :key="'d' + c.id"
                  />
                </template>
              </v-list>
            </v-card>
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

          <!-- SIMULADOR -->
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

          <!-- METAS -->
          <div v-else-if="view === 'goals'" key="goals">
            <goals-page
              :api-base="apiBase"
              :categories="categories"
              :refresh-key="goalsRefreshKey"
              @error="showError"
              @saved="onGoalsSaved"
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
          data-tour="finance-fab-transaction"
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
      :transaction="editTransaction"
      @saved="onSaved"
      @error="showError"
      @create-category="onCreateCategoryFromTransaction"
    />

    <category-form-dialog
      v-model="categoryDialog"
      :api-base="apiBase"
      :category="editCategory"
      :initial-type="categoryDialogInitialType"
      @saved="onCategorySaved"
      @error="showError"
    />

    <v-dialog
      v-model="duplicateDialog.open"
      max-width="520"
      persistent
      content-class="duplicate-transaction-dialog"
    >
      <v-card class="rounded-lg duplicate-transaction-card">
        <v-card-title class="headline pb-2 px-4 pt-4"
          >Duplicar transação</v-card-title
        >
        <v-card-text class="pa-4 pt-2 d-flex flex-column">
          <p class="body-2 finance-text-muted mb-0">
            Cópias nos meses seguintes à data deste lançamento, com o mesmo
            valor, categoria e tipo. A transação original não é alterada.
          </p>

          <v-row dense class="mt-4">
            <v-col cols="12" class="d-flex flex-column">
              <span class="text-subtitle-2 font-weight-medium mb-1"
                >Quantos meses à frente?</span
              >
              <span class="text-caption finance-text-muted mb-2"
                >Arraste o controle ou digite de 1 a 60.</span
              >
              <v-slider
                v-model="duplicateSliderModel"
                class="duplicate-transaction-slider mt-1"
                min="1"
                max="60"
                step="1"
                thumb-label="always"
                color="primary"
                track-color="secondary"
                hide-details
                :disabled="duplicateDialog.loading"
              />
            </v-col>

            <v-col cols="12" class="d-flex align-start flex-nowrap">
              <v-btn
                icon
                small
                class="mt-1 flex-shrink-0"
                type="button"
                aria-label="Diminuir meses"
                :disabled="duplicateDialog.loading || duplicateSliderModel <= 1"
                @click="bumpDuplicateMonths(-1)"
              >
                <v-icon small>mdi-minus</v-icon>
              </v-btn>
              <v-text-field
                class="mx-2 flex-grow-1"
                outlined
                dense
                label="Número de meses"
                type="number"
                min="1"
                max="60"
                hide-details="auto"
                :value="duplicateMonthsFieldDisplay"
                :error-messages="
                  duplicateMonthsError ? [duplicateMonthsError] : []
                "
                :disabled="duplicateDialog.loading"
                @input="onDuplicateMonthsInput"
              />
              <v-btn
                icon
                small
                class="mt-1 flex-shrink-0"
                type="button"
                aria-label="Aumentar meses"
                :disabled="
                  duplicateDialog.loading || duplicateSliderModel >= 60
                "
                @click="bumpDuplicateMonths(1)"
              >
                <v-icon small>mdi-plus</v-icon>
              </v-btn>
            </v-col>

            <v-col cols="12" class="pt-1">
              <div class="text-caption secondary--text mb-1">Atalhos</div>
              <div class="d-flex flex-wrap" style="gap: 8px">
                <v-chip
                  v-for="preset in duplicateMonthPresets"
                  :key="preset"
                  small
                  outlined
                  :disabled="duplicateDialog.loading"
                  @click="setDuplicateMonths(preset)"
                >
                  {{ preset }} {{ preset === 1 ? "mês" : "meses" }}
                </v-chip>
              </div>
            </v-col>
          </v-row>

          <transition name="duplicate-preview-fade">
            <v-sheet
              v-if="duplicateTransactionPreviewCount"
              :key="'preview-' + duplicateTransactionPreviewCount"
              outlined
              rounded
              class="pa-3 mt-4 duplicate-preview-sheet"
            >
              <div class="body-2 font-weight-medium primary--text">
                Isso criará {{ duplicateTransactionPreviewCount }}
                {{
                  duplicateTransactionPreviewCount === 1
                    ? "transação futura"
                    : "transações futuras"
                }}.
              </div>
              <div
                v-if="duplicatePreviewMonthsLine"
                class="text-caption finance-text-muted mt-2 mb-0"
              >
                Meses: {{ duplicatePreviewMonthsLine }}
              </div>
            </v-sheet>
          </transition>
        </v-card-text>
        <v-divider />
        <v-card-actions
          class="pa-4 d-flex flex-wrap justify-end"
          style="gap: 8px"
        >
          <v-btn
            text
            color="secondary"
            class="text-none"
            :disabled="duplicateDialog.loading"
            @click="closeDuplicateDialog"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="primary"
            depressed
            large
            class="px-6 text-none font-weight-medium"
            :loading="duplicateDialog.loading"
            :disabled="!canConfirmDuplicate"
            @click="confirmDuplicate"
          >
            <v-icon left>mdi-content-copy</v-icon>
            Duplicar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDialog.open" max-width="400" persistent>
      <v-card class="rounded-lg">
        <v-card-title class="headline">Excluir transação?</v-card-title>
        <v-card-text class="finance-text-muted"
          >Esta ação não pode ser desfeita.</v-card-text
        >
        <v-card-actions>
          <v-btn text color="secondary" @click="deleteDialog.open = false"
            >Cancelar</v-btn
          >
          <v-spacer />
          <v-btn
            color="error"
            depressed
            :loading="deleteDialog.loading"
            @click="confirmDelete"
            >Excluir</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <finance-help-modal v-model="helpDialog" />

    <v-dialog v-model="deleteCatDialog.open" max-width="400" persistent>
      <v-card class="rounded-lg">
        <v-card-title class="headline">Excluir categoria?</v-card-title>
        <v-card-text class="finance-text-muted"
          >Transações associadas ficam sem categoria.</v-card-text
        >
        <v-card-actions>
          <v-btn text color="secondary" @click="deleteCatDialog.open = false"
            >Cancelar</v-btn
          >
          <v-spacer />
          <v-btn
            color="error"
            depressed
            :loading="deleteCatDialog.loading"
            @click="confirmDeleteCategory"
            >Excluir</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <onboarding-tour
      v-if="!onboardingCompletedLocal"
      v-model="onboardingTourOpen"
      :onboarding-complete-url="onboardingCompleteUrl"
      :return-month="month"
      @step="onOnboardingStep"
    />
  </v-app>
</template>

<script>
import axios from "axios";
import Dashboard from "../../components/Dashboard.vue";
import TransactionList from "../../components/TransactionList.vue";
import TransactionForm from "../../components/TransactionForm.vue";
import CategoryFormDialog from "../../components/CategoryFormDialog.vue";
import ReportsPage from "../reports/ReportsPage.vue";
import ProjectionPage from "../projection/ProjectionPage.vue";
import AlertsPage from "../alerts/AlertsPage.vue";
import InsightsPage from "../insights/InsightsPage.vue";
import CreditSimulatorPage from "../simulator/CreditSimulatorPage.vue";
import PlanningPage from "../planning/PlanningPage.vue";
import GoalsPage from "../goals/GoalsPage.vue";
import FinanceThemeToggle from "../../components/FinanceThemeToggle.vue";
import FinanceHelpModal from "../../components/FinanceHelpModal.vue";
import OnboardingTour from "../../components/OnboardingTour.vue";
import {
  addMonthsToYearMonth,
  monthChoices,
  normalizeMonth,
  toIsoDateOnly,
} from "../../format";
import { applyBodyThemeClass, getStoredTheme } from "../../financeTheme";

const VALID_VIEWS = [
  "dashboard",
  "transactions",
  "categories",
  "goals",
  "projection",
  "reports",
  "alerts",
  "insights",
  "simulator",
  "planning",
];

export default {
  name: "FinanceApp",
  components: {
    Dashboard,
    TransactionList,
    TransactionForm,
    CategoryFormDialog,
    ReportsPage,
    ProjectionPage,
    AlertsPage,
    InsightsPage,
    CreditSimulatorPage,
    PlanningPage,
    GoalsPage,
    FinanceThemeToggle,
    FinanceHelpModal,
    OnboardingTour,
  },
  props: {
    initialView: { type: String, default: "dashboard" },
    initialMonth: { type: String, default: "" },
    apiBase: { type: String, required: true },
    userName: { type: String, default: "" },
    onboardingInitialCompleted: { type: Boolean, default: false },
    onboardingCompleteUrl: { type: String, required: true },
    isAdmin: { type: Boolean, default: false },
    /** Slugs SaaS atribuídos ao utilizador (opcionais além do núcleo com `finance`). */
    userModuleSlugs: { type: Array, default: () => [] },
  },
  data() {
    const v = VALID_VIEWS.includes(this.initialView)
      ? this.initialView
      : "dashboard";
    return {
      onboardingCompletedLocal: this.onboardingInitialCompleted,
      onboardingTourOpen: !this.onboardingInitialCompleted,
      navDrawer: false,
      view: v,
      month: normalizeMonth(this.initialMonth),
      monthItems: monthChoices(24),
      dashboardRefreshKey: 0,
      reportsRefreshKey: 0,
      projectionRefreshKey: 0,
      alertsRefreshKey: 0,
      insightsRefreshKey: 0,
      simulatorRefreshKey: 0,
      planningRefreshKey: 0,
      goalsRefreshKey: 0,
      /**
       * core: visível para qualquer utilizador com módulo `finance` (entrada na app).
       * !core: só com slug extra no pivot (checkbox no admin).
       */
      navItemsAll: [
        {
          title: "Dashboard",
          value: "dashboard",
          icon: "mdi-view-dashboard-outline",
          core: true,
        },
        {
          title: "Movimentações",
          value: "transactions",
          icon: "mdi-bank-transfer",
          core: true,
        },
        {
          title: "Categorias",
          value: "categories",
          icon: "mdi-shape-outline",
          core: true,
        },
        {
          title: "Metas",
          value: "goals",
          icon: "mdi-flag-checkered",
          core: true,
        },
        {
          title: "Projeção",
          value: "projection",
          icon: "mdi-chart-timeline-variant",
          core: false,
          slug: "projections",
        },
        {
          title: "Relatórios",
          value: "reports",
          icon: "mdi-file-chart-outline",
          core: false,
          slug: "reports",
        },
        {
          title: "Alertas",
          value: "alerts",
          icon: "mdi-bell-alert-outline",
          core: true,
        },
        {
          title: "Insights",
          value: "insights",
          icon: "mdi-lightbulb-outline",
          core: true,
        },
        {
          title: "Simulador",
          value: "simulator",
          icon: "mdi-calculator-variant",
          core: true,
        },
        {
          title: "Planejamento",
          value: "planning",
          icon: "mdi-calendar-check",
          core: false,
          slug: "planning",
        },
      ],
      transactions: [],
      transactionsPage: 1,
      transactionsMeta: null,
      loadingMoreTransactions: false,
      txSummary: null,
      categories: [],
      filterCategoryId: null,
      loading: {
        transactions: false,
        categories: false,
      },
      snackbar: { show: false, text: "", color: "primary" },
      formOpen: false,
      editTransaction: null,
      duplicateDialog: { open: false, loading: false, item: null, months: 1 },
      duplicateMonthPresets: [1, 2, 3, 6, 12],
      deleteDialog: { open: false, loading: false, item: null },
      categoryDialog: false,
      categoryDialogInitialType: null,
      editCategory: null,
      deleteCatDialog: { open: false, loading: false, item: null },
      helpDialog: false,
    };
  },
  computed: {
    navItems() {
      if (this.isAdmin) {
        return this.navItemsAll;
      }
      return this.navItemsAll.filter(
        (item) =>
          item.core || (item.slug && this.userModuleSlugs.includes(item.slug)),
      );
    },
    categoryFilterItems() {
      return this.categories.map((c) => ({ text: c.name, value: c.id }));
    },
    monthLabelPt() {
      if (!this.month || !/^\d{4}-\d{2}$/.test(this.month)) return "—";
      const [y, m] = this.month.split("-");
      return `${m}/${y}`;
    },
    /** Mês anterior ao selecionado (rótulo mm/aaaa), alinhado a `acumulado_ate_inicio_mes`. */
    monthLabelPreviousPt() {
      if (!this.month || !/^\d{4}-\d{2}$/.test(this.month)) return "—";
      const prev = addMonthsToYearMonth(this.month, -1);
      if (!prev) return "—";
      const [y, m] = prev.split("-");
      return `${m}/${y}`;
    },
    txSaldoAcumuladoClass() {
      const n = Number(
        this.txSummary && this.txSummary.saldo_acumulado_ate_mes,
      );
      if (n > 0) return "success--text";
      if (n < 0) return "error--text";
      return "";
    },
    txResultadoMesClass() {
      const n = Number(this.txSummary && this.txSummary.available_this_month);
      if (n > 0) return "success--text";
      if (n < 0) return "error--text";
      return "";
    },
    showMonthSelector() {
      return [
        "dashboard",
        "transactions",
        "reports",
        "alerts",
        "insights",
      ].includes(this.view);
    },
    showTransactionFab() {
      return ["dashboard", "transactions"].includes(this.view);
    },
    canLoadMoreTransactions() {
      const m = this.transactionsMeta;
      if (!m || !m.last_page) return false;
      return m.current_page < m.last_page;
    },
    canShiftMonthOlder() {
      const prev = addMonthsToYearMonth(this.month, -1);
      return prev !== "" && this.monthItems.some((x) => x.value === prev);
    },
    canShiftMonthNewer() {
      const next = addMonthsToYearMonth(this.month, 1);
      return next !== "" && this.monthItems.some((x) => x.value === next);
    },
    duplicateSliderModel: {
      get() {
        const m = parseInt(String(this.duplicateDialog.months), 10);
        if (!Number.isFinite(m)) return 1;
        return Math.min(60, Math.max(1, m));
      },
      set(v) {
        const n = typeof v === "number" ? v : parseInt(String(v), 10);
        if (Number.isFinite(n)) {
          this.duplicateDialog.months = Math.min(60, Math.max(1, n));
        }
      },
    },
    duplicateMonthsFieldDisplay() {
      const m = this.duplicateDialog.months;
      if (m === "" || m === null || m === undefined) return "";
      return String(m);
    },
    duplicateMonthsError() {
      if (!this.duplicateDialog.open) return "";
      const raw = this.duplicateDialog.months;
      if (raw === "" || raw === null || raw === undefined) {
        return "Informe um valor entre 1 e 60.";
      }
      const m = parseInt(String(raw), 10);
      if (!Number.isFinite(m)) {
        return "Use um número inteiro.";
      }
      if (m < 1) {
        return "O mínimo é 1 mês.";
      }
      if (m > 60) {
        return "No máximo 60 meses por vez.";
      }
      return "";
    },
    duplicateTransactionPreviewCount() {
      if (this.duplicateMonthsError) {
        return null;
      }
      return parseInt(String(this.duplicateDialog.months), 10);
    },
    duplicatePreviewMonthLabels() {
      const n = this.duplicateTransactionPreviewCount;
      const item = this.duplicateDialog.item;
      if (!n || !item) {
        return [];
      }
      const iso = toIsoDateOnly(item.transaction_date);
      if (!iso) {
        return [];
      }
      const [ys, ms, ds] = iso.split("-").map((x) => parseInt(x, 10));
      const out = [];
      for (let i = 1; i <= n; i += 1) {
        const dt = new Date(ys, ms - 1, ds);
        dt.setMonth(dt.getMonth() + i);
        const label = new Intl.DateTimeFormat("pt-BR", {
          month: "short",
          year: "numeric",
        }).format(dt);
        out.push(
          label
            .replace(/\./g, "")
            .replace(/\s{2,}/g, " ")
            .trim(),
        );
      }
      return out;
    },
    duplicatePreviewMonthsLine() {
      const labels = this.duplicatePreviewMonthLabels;
      if (!labels.length) {
        return "";
      }
      const cap = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);
      const maxInline = 6;
      if (labels.length <= maxInline) {
        return labels.map(cap).join(" · ");
      }
      const head = labels.slice(0, 3).map(cap).join(" · ");
      const rest = labels.length - 3;
      return `${head} · … (+${rest} ${rest === 1 ? "mês" : "meses"})`;
    },
    canConfirmDuplicate() {
      return (
        !this.duplicateDialog.loading &&
        this.duplicateMonthsError === "" &&
        this.duplicateTransactionPreviewCount !== null
      );
    },
  },
  watch: {
    categoryDialog(val) {
      if (!val) this.categoryDialogInitialType = null;
    },
    view(v) {
      if (v === "categories") this.loadCategories();
      if (v === "reports") {
        this.reportsRefreshKey += 1;
      }
      if (v === "projection") {
        this.projectionRefreshKey += 1;
      }
      if (v === "alerts") {
        this.alertsRefreshKey += 1;
      }
      if (v === "insights") {
        this.insightsRefreshKey += 1;
      }
      if (v === "simulator") {
        this.simulatorRefreshKey += 1;
      }
      if (v === "planning") {
        this.planningRefreshKey += 1;
      }
      if (v === "goals") {
        this.goalsRefreshKey += 1;
      }
    },
  },
  created() {
    applyBodyThemeClass(this.$vuetify.theme.dark);
    if (
      typeof window !== "undefined" &&
      window.matchMedia &&
      getStoredTheme() === null
    ) {
      this._schemeMq = window.matchMedia("(prefers-color-scheme: dark)");
      this._onSchemeChange = () => {
        if (getStoredTheme() !== null) return;
        this.$vuetify.theme.dark = this._schemeMq.matches;
        applyBodyThemeClass(this.$vuetify.theme.dark);
      };
      this._schemeMq.addEventListener("change", this._onSchemeChange);
    }
  },
  beforeDestroy() {
    if (this._schemeMq && this._onSchemeChange) {
      this._schemeMq.removeEventListener("change", this._onSchemeChange);
    }
  },
  mounted() {
    if (!this.canShowView(this.view)) {
      this.view = this.firstAllowedView();
    }
    this.refreshAll();
  },
  methods: {
    canShowView(value) {
      const item = this.navItemsAll.find((i) => i.value === value);
      if (!item) return false;
      if (this.isAdmin) return true;
      if (item.core) return true;
      return Boolean(item.slug && this.userModuleSlugs.includes(item.slug));
    },
    firstAllowedView() {
      const items = this.navItems;
      return items.length ? items[0].value : "dashboard";
    },
    firstAllowedNavTitle() {
      const items = this.navItems;
      return items.length ? items[0].title : "Dashboard";
    },
    recoverToFirstAllowedView() {
      this.view = this.firstAllowedView();
      if (this.$vuetify.breakpoint.smAndDown) {
        this.navDrawer = false;
      }
    },
    setViewOnly(value) {
      if (!this.canShowView(value)) return;
      this.view = value;
    },
    onOnboardingStep(stepIndex) {
      if (stepIndex === 2) {
        this.setViewOnly("dashboard");
        if (this.$vuetify.breakpoint.smAndDown) this.navDrawer = true;
        return;
      }
      this.setViewOnly("dashboard");
      if (this.$vuetify.breakpoint.smAndDown) this.navDrawer = false;
      if (stepIndex === 3) this.dashboardRefreshKey += 1;
    },
    logout() {
      const f = document.getElementById("finance-logout-form");
      if (f) f.submit();
    },
    chipBg(c) {
      if (c.color && /^#[0-9A-Fa-f]{6}$/.test(c.color)) return c.color;
      return "secondary";
    },
    categoryTypeLabel(c) {
      if (c.type === "income") return "Receita";
      return "Despesa";
    },
    async refreshAll() {
      this.dashboardRefreshKey += 1;
      this.reportsRefreshKey += 1;
      this.projectionRefreshKey += 1;
      this.alertsRefreshKey += 1;
      this.insightsRefreshKey += 1;
      this.simulatorRefreshKey += 1;
      this.planningRefreshKey += 1;
      this.goalsRefreshKey += 1;
      await Promise.all([
        this.loadTransactions({ reset: true }),
        this.loadCategories(),
      ]);
    },
    goView(value) {
      if (!this.canShowView(value)) {
        this.showError("Sem permissão para este módulo.");
        return;
      }
      this.view = value;
      if (this.$vuetify.breakpoint.smAndDown) {
        this.navDrawer = false;
      }
    },
    /** Troca de mês: lista de transações + Dashboard observa `month` (sem segundo fetch por key). */
    async onMonthChange() {
      await this.loadTransactions({ reset: true });
    },
    onGoalsSaved() {
      this.toast("Metas atualizadas.", "success");
      this.goalsRefreshKey += 1;
    },
    shiftMonthOlder() {
      const prev = addMonthsToYearMonth(this.month, -1);
      if (prev && this.monthItems.some((x) => x.value === prev)) {
        this.month = prev;
        this.onMonthChange();
      }
    },
    shiftMonthNewer() {
      const next = addMonthsToYearMonth(this.month, 1);
      if (next && this.monthItems.some((x) => x.value === next)) {
        this.month = next;
        this.onMonthChange();
      }
    },
    formatBRL(v) {
      return this.$formatCurrencyBRL(v);
    },
    /**
     * Lista paginada (page, per_page) na API; reset=true volta à página 1 e substitui a lista.
     */
    async loadTransactions(options = {}) {
      const reset = options.reset !== false;
      if (reset) {
        this.transactionsPage = 1;
        this.transactionsMeta = null;
      }
      this.loading.transactions = !reset ? false : true;
      this.loadingMoreTransactions = !reset;
      try {
        const params = {
          month: this.month,
          page: this.transactionsPage,
          per_page: 20,
        };
        if (this.filterCategoryId) params.category_id = this.filterCategoryId;
        const [txOut, sumOut] = await Promise.allSettled([
          axios.get(`${this.apiBase}/transactions`, { params }),
          axios.get(`${this.apiBase}/summary`, {
            params: { month: this.month },
          }),
        ]);
        if (txOut.status === "fulfilled") {
          const body = txOut.value.data;
          const chunk = body.data || [];
          this.transactionsMeta = body.meta || null;
          if (reset) {
            this.transactions = chunk;
          } else {
            this.transactions = [...this.transactions, ...chunk];
          }
        } else {
          if (reset) {
            this.transactions = [];
            this.transactionsMeta = null;
          }
        }
        if (sumOut.status === "fulfilled") {
          this.txSummary = sumOut.value.data || null;
        } else {
          this.txSummary = null;
        }
      } finally {
        this.loading.transactions = false;
        this.loadingMoreTransactions = false;
      }
    },
    async loadMoreTransactions() {
      if (!this.canLoadMoreTransactions) return;
      this.transactionsPage += 1;
      await this.loadTransactions({ reset: false });
    },
    async loadCategories() {
      this.loading.categories = true;
      try {
        const { data } = await axios.get(`${this.apiBase}/categories`);
        this.categories = data.data || [];
      } catch (e) {
        this.categories = [];
      } finally {
        this.loading.categories = false;
      }
    },
    openCreate() {
      this.editTransaction = null;
      this.formOpen = true;
    },
    openEdit(item) {
      this.editTransaction = { ...item };
      this.formOpen = true;
    },
    openDuplicateDialog(transaction) {
      this.duplicateDialog.item = transaction;
      this.duplicateDialog.months = 1;
      this.duplicateDialog.open = true;
    },
    closeDuplicateDialog() {
      this.duplicateDialog.open = false;
    },
    setDuplicateMonths(n) {
      const v = parseInt(String(n), 10);
      if (Number.isFinite(v)) {
        this.duplicateDialog.months = Math.min(60, Math.max(1, v));
      }
    },
    bumpDuplicateMonths(delta) {
      let m = parseInt(String(this.duplicateDialog.months), 10);
      if (!Number.isFinite(m)) {
        m = 1;
      }
      this.duplicateDialog.months = Math.min(60, Math.max(1, m + delta));
    },
    onDuplicateMonthsInput(val) {
      if (val === "" || val === null || val === undefined) {
        this.duplicateDialog.months = "";
        return;
      }
      const m = parseInt(String(val), 10);
      if (!Number.isFinite(m)) {
        this.duplicateDialog.months = val;
        return;
      }
      this.duplicateDialog.months = Math.min(60, Math.max(1, m));
    },
    async confirmDuplicate() {
      if (!this.duplicateDialog.item) return;
      let m = parseInt(String(this.duplicateDialog.months), 10);
      if (!Number.isFinite(m) || m < 1) m = 1;
      if (m > 60) m = 60;
      this.duplicateDialog.loading = true;
      try {
        const { data } = await axios.post(
          `${this.apiBase}/transactions/${this.duplicateDialog.item.id}/duplicate`,
          { months: m },
        );
        const n = (data && data.count) || 0;
        this.duplicateDialog.open = false;
        this.toast(
          n ? `${n} cópia(s) criada(s).` : "Cópias criadas.",
          "success",
        );
        this.refreshAll();
      } catch (e) {
        const d = e.response && e.response.data;
        let msg =
          (d && d.message) ||
          (d && d.errors && d.errors.months && d.errors.months[0]) ||
          "Não foi possível duplicar.";
        this.showError(msg);
      } finally {
        this.duplicateDialog.loading = false;
        this.duplicateDialog.item = null;
      }
    },
    askDelete(item) {
      this.deleteDialog.item = item;
      this.deleteDialog.open = true;
    },
    async confirmDelete() {
      if (!this.deleteDialog.item) return;
      this.deleteDialog.loading = true;
      try {
        await axios.delete(
          `${this.apiBase}/transactions/${this.deleteDialog.item.id}`,
        );
        this.deleteDialog.open = false;
        this.toast("Transação removida.", "success");
        this.refreshAll();
      } catch (e) {
        this.showError("Não foi possível excluir.");
      } finally {
        this.deleteDialog.loading = false;
        this.deleteDialog.item = null;
      }
    },
    onCreateCategoryFromTransaction(payload) {
      this.editCategory = null;
      this.categoryDialogInitialType = (payload && payload.type) || null;
      this.categoryDialog = true;
    },
    openCategoryCreate() {
      this.editCategory = null;
      this.categoryDialogInitialType = null;
      this.categoryDialog = true;
    },
    openCategoryEdit(c) {
      this.editCategory = { ...c };
      this.categoryDialogInitialType = null;
      this.categoryDialog = true;
    },
    askDeleteCategory(c) {
      this.deleteCatDialog.item = c;
      this.deleteCatDialog.open = true;
    },
    async confirmDeleteCategory() {
      if (!this.deleteCatDialog.item) return;
      this.deleteCatDialog.loading = true;
      try {
        await axios.delete(
          `${this.apiBase}/categories/${this.deleteCatDialog.item.id}`,
        );
        this.deleteCatDialog.open = false;
        this.toast("Categoria removida.", "success");
        await this.loadCategories();
        this.refreshAll();
      } catch (e) {
        this.showError("Não foi possível excluir a categoria.");
      } finally {
        this.deleteCatDialog.loading = false;
        this.deleteCatDialog.item = null;
      }
    },
    onCategorySaved() {
      this.toast("Categoria guardada.", "success");
      this.editCategory = null;
      this.loadCategories().then(() => this.refreshAll());
    },
    onSaved() {
      this.toast(
        this.editTransaction ? "Transação atualizada." : "Transação criada.",
        "success",
      );
      this.editTransaction = null;
      this.refreshAll();
    },
    toast(text, color = "primary") {
      this.snackbar = { show: true, text, color };
    },
    showError(msg) {
      this.snackbar = { show: true, text: msg, color: "error" };
    },
  },
};
</script>

<style scoped>
.finance-v-app {
  min-height: 100vh !important;
}
.finance-fab {
  bottom: 24px !important;
  right: 24px !important;
  z-index: 24;
  transition: transform 0.2s ease, box-shadow 0.2s ease !important;
}
.finance-fab--primary-action:hover {
  transform: scale(1.04);
  box-shadow: 0 8px 28px rgba(255, 0, 0, 0.38) !important;
}
.finance-fab--secondary-action:hover {
  transform: scale(1.04);
  box-shadow: 0 8px 26px rgba(133, 136, 143, 0.4) !important;
}

.duplicate-preview-fade-enter-active,
.duplicate-preview-fade-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.duplicate-preview-fade-enter,
.duplicate-preview-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.duplicate-transaction-card
  .duplicate-transaction-slider
  ::v-deep
  .v-slider__thumb-label {
  font-size: 12px;
  font-weight: 600;
}

.theme--dark .duplicate-preview-sheet {
  border-color: rgba(255, 255, 255, 0.12) !important;
}

.finance-month-bar {
  min-width: 0;
}
.theme--dark .finance-month-bar {
  border-color: rgba(255, 255, 255, 0.12) !important;
}
.finance-month-bar__hint {
  padding-left: 30px;
}
@media (min-width: 960px) {
  .finance-month-bar__hint {
    padding-left: 30px;
    max-width: 280px;
  }
}
.finance-month-bar__controls {
  min-width: 0;
}
.finance-month-select {
  min-width: 0;
}
@media (min-width: 600px) {
  .finance-month-select {
    max-width: 360px;
  }
}
.finance-month-select__selection {
  line-height: 1.3;
}

/* Três cartões de resumo na mesma altura (sm+); coluna empilha em xs */
.tx-summary-cards-row .tx-summary-card {
  width: 100%;
  min-height: 100%;
}
</style>
