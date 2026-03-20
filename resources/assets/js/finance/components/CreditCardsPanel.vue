<template>
  <div class="cc-panel">
    <v-row dense class="mb-4 align-center">
      <v-col cols="12" sm="auto" class="d-flex flex-wrap align-center gap-2">
        <v-btn
          class="cc-panel__btn-new"
          color="primary"
          depressed
          rounded
          large
          @click="openCreate"
        >
          <v-icon left>mdi-plus</v-icon>
          Novo cartão
        </v-btn>
        <span class="text-caption secondary--text px-1">
          Compras no cartão entram na fatura, não no caixa imediato.
        </span>
      </v-col>
    </v-row>

    <v-row dense>
      <!-- 1. Lista de cartões — primeiro no scroll (mobile-first), coluna estreita no desktop -->
      <v-col cols="12" lg="5">
        <v-card class="cc-panel__sidebar rounded-xl" flat outlined>
          <v-card-title class="subtitle-1 font-weight-bold py-4 border-b">
            <v-icon left color="secondary">mdi-wallet-outline</v-icon>
            Meus cartões
          </v-card-title>
          <v-list v-if="!loadingList && cards.length" class="py-2" nav>
            <v-list-item
              v-for="c in cards"
              :key="c.id"
              :input-value="selectedId === c.id"
              class="cc-picker-item mx-2 mb-2 rounded-lg"
              :class="{ 'cc-picker-item--active': selectedId === c.id }"
              @click="selectCard(c)"
            >
              <v-list-item-avatar class="my-2">
                <v-avatar :color="selectedId === c.id ? 'primary' : 'secondary'" size="44">
                  <v-icon dark small>mdi-credit-card-chip</v-icon>
                </v-avatar>
              </v-list-item-avatar>
              <v-list-item-content>
                <v-list-item-title class="font-weight-semibold text-truncate">{{ c.name }}</v-list-item-title>
                <v-list-item-subtitle class="tabular-nums">
                  Limite {{ formatBRL(c.limit) }}
                </v-list-item-subtitle>
              </v-list-item-content>
              <v-list-item-action class="flex-row ma-0">
                <v-btn icon x-small @click.stop="openEdit(c)" aria-label="Editar">
                  <v-icon small color="secondary">mdi-pencil-outline</v-icon>
                </v-btn>
                <v-btn icon x-small @click.stop="askDelete(c)" aria-label="Excluir">
                  <v-icon small color="secondary">mdi-delete-outline</v-icon>
                </v-btn>
              </v-list-item-action>
            </v-list-item>
          </v-list>
          <v-card-text v-else-if="loadingList" class="text-center py-10">
            <v-progress-circular indeterminate color="primary" size="32" />
          </v-card-text>
          <v-card-text v-else class="text-center py-12 secondary--text">
            <v-icon size="48" color="secondary">mdi-credit-card-plus-outline</v-icon>
            <p class="mt-3 mb-0 caption">Cadastre um cartão para começar.</p>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- 2. Detalhe / fatura / compras -->
      <v-col cols="12" lg="7">
        <v-card
          v-if="!loadingList && cards.length && !selectedId"
          class="cc-panel__placeholder rounded-xl pa-8 text-center"
          flat
          outlined
        >
          <v-icon size="52" color="secondary">mdi-credit-card-search-outline</v-icon>
          <p class="mt-3 mb-0 body-2 secondary--text">
            Toque em um cartão para ver limite, fatura e compras.
          </p>
        </v-card>

        <div v-else-if="selectedId && bill" class="cc-panel__detail">
          <v-overlay v-if="billLoading" absolute color="surface" opacity="0.92" z-index="4">
            <v-progress-circular indeterminate size="40" color="primary" />
          </v-overlay>

          <!-- “Cartão” decorativo -->
          <div class="cc-card-hero cc-card-hero__text rounded-t-xl px-4 py-5">
            <div class="d-flex align-start">
              <div class="min-w-0 flex-grow-1">
                <div class="cc-card-hero__chip text-caption text-uppercase opacity-75 mb-1">
                  Cartão de crédito
                </div>
                <h2 class="cc-card-hero__name text-h5 text-sm-h4 font-weight-bold text-truncate">
                  {{ bill.credit_card.name }}
                </h2>
                <div class="d-flex flex-wrap align-center mt-2 caption opacity-90">
                  <v-icon x-small color="white" class="mr-1">mdi-calendar-clock</v-icon>
                  Fecha {{ bill.credit_card.closing_day }}
                  <span class="mx-2">·</span>
                  Vence {{ bill.credit_card.due_day }}
                </div>
              </div>
              <v-icon size="40" class="opacity-40 ml-2 flex-shrink-0">mdi-contactless-payment</v-icon>
            </div>
          </div>

          <v-card class="cc-panel__body rounded-b-xl rounded-t-0" flat outlined>
            <v-card-text class="pt-5 px-4 pb-2">
              <!-- Limite / usado / disponível -->
              <v-row dense class="mb-2">
                <v-col cols="12" sm="4">
                  <div class="cc-stat">
                    <div class="cc-stat__label">Limite</div>
                    <div class="cc-stat__value tabular-nums">{{ formatBRL(bill.credit_card.limit) }}</div>
                  </div>
                </v-col>
                <v-col cols="12" sm="4">
                  <div class="cc-stat">
                    <div class="cc-stat__label">Usado (total)</div>
                    <div class="cc-stat__value cc-stat__value--used tabular-nums">
                      {{ formatBRL(bill.utilizado_no_cartao) }}
                    </div>
                  </div>
                </v-col>
                <v-col cols="12" sm="4">
                  <div class="cc-stat">
                    <div class="cc-stat__label">Disponível</div>
                    <div
                      class="cc-stat__value font-weight-bold tabular-nums"
                      :class="bill.limite_disponivel > 0 ? 'success--text' : 'error--text'"
                    >
                      {{ formatBRL(bill.limite_disponivel) }}
                    </div>
                  </div>
                </v-col>
              </v-row>

              <!-- Barra de uso -->
              <div class="cc-usage mb-3">
                <div class="d-flex justify-space-between align-center mb-1">
                  <span class="caption font-weight-medium secondary--text">Uso do limite</span>
                  <span
                    class="caption font-weight-bold tabular-nums"
                    :class="usageRisk.textClass"
                  >
                    {{ usagePercentDisplay }}%
                  </span>
                </div>
                <div class="cc-usage__track">
                  <div
                    class="cc-usage__fill"
                    :class="usageRisk.fillClass"
                    :style="{ width: usagePercentCapped + '%' }"
                  />
                </div>
                <div v-if="usageRisk.level !== 'ok'" class="mt-2">
                  <v-alert
                    dense
                    border="left"
                    colored-border
                    :type="usageRisk.level === 'danger' ? 'error' : 'warning'"
                    class="caption py-2 mb-0"
                    :icon="usageRisk.level === 'danger' ? 'mdi-alert-octagon' : 'mdi-alert'"
                  >
                    {{ usageRisk.message }}
                  </v-alert>
                </div>
              </div>

              <!-- Fatura — destaque principal -->
              <div class="cc-fatura rounded-xl pa-4 mb-4">
                <div class="text-overline font-weight-medium cc-fatura__kicker mb-1">
                  Fatura atual
                </div>
                <div class="cc-fatura__period caption mb-2 opacity-85">
                  Período {{ formatPeriod(bill.period) }}
                </div>
                <div class="cc-fatura__amount tabular-nums">
                  {{ formatBRL(bill.fatura_total) }}
                </div>
                <div class="caption mt-2 opacity-85">
                  Soma das compras neste fechamento · valores em R$
                </div>
              </div>

              <!-- Lista de compras -->
              <div class="cc-purchases-head d-flex align-center mb-2">
                <v-icon color="secondary" class="mr-2" size="22">mdi-receipt-text-outline</v-icon>
                <span class="subtitle-2 font-weight-bold">Compras na fatura</span>
                <v-chip v-if="purchaseCount" x-small outlined class="ml-2">{{ purchaseCount }}</v-chip>
              </div>

              <template v-if="!bill.transacoes || bill.transacoes.length === 0">
                <div class="cc-purchases-empty text-center py-8 rounded-lg">
                  <v-icon color="secondary" size="40">mdi-cart-off</v-icon>
                  <p class="caption secondary--text mt-2 mb-0">Nenhuma compra nesta fatura.</p>
                </div>
              </template>
              <v-list v-else class="cc-purchases-list py-0 transparent" nav dense>
                <v-list-item
                  v-for="t in bill.transacoes"
                  :key="t.id"
                  class="cc-purchase-item px-2 px-sm-3 rounded-lg mb-1"
                  :ripple="false"
                >
                  <v-list-item-content class="py-2">
                    <div class="d-flex align-center justify-space-between flex-wrap">
                      <div class="cc-purchase-item__date caption secondary--text tabular-nums">
                        <v-icon x-small class="mr-1" color="secondary">mdi-calendar-outline</v-icon>
                        {{ formatPurchaseDate(t.transaction_date) }}
                      </div>
                      <div class="cc-purchase-item__amount font-weight-bold tabular-nums error--text">
                        {{ formatBRL(t.amount) }}
                      </div>
                    </div>
                    <v-list-item-title class="cc-purchase-item__title text-body-2 font-weight-medium pt-1">
                      {{ t.title }}
                    </v-list-item-title>
                    <v-list-item-subtitle v-if="t.category" class="text-caption">
                      {{ t.category.name }}
                    </v-list-item-subtitle>
                  </v-list-item-content>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </div>

        <v-card v-else-if="loadingList" class="rounded-xl pa-10 text-center" flat outlined>
          <v-progress-circular indeterminate color="primary" />
        </v-card>
      </v-col>
    </v-row>

    <credit-card-form-dialog
      v-model="formOpen"
      :api-base="apiBase"
      :card="editCard"
      @saved="onCardSaved"
      @error="$emit('error', $event)"
    />

    <v-dialog v-model="deleteDialog.open" max-width="400" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="headline font-weight-bold">Excluir cartão?</v-card-title>
        <v-card-text class="text--secondary">Transações ligadas ficam sem vínculo com o cartão.</v-card-text>
        <v-card-actions class="pb-4 px-4">
          <v-btn text rounded @click="deleteDialog.open = false">Cancelar</v-btn>
          <v-spacer />
          <v-btn color="error" depressed rounded :loading="deleteDialog.loading" @click="confirmDelete">
            Excluir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import axios from 'axios'
import CreditCardFormDialog from './CreditCardFormDialog.vue'

export default {
  name: 'CreditCardsPanel',
  components: { CreditCardFormDialog },
  props: {
    apiBase: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      cards: [],
      loadingList: true,
      billLoading: false,
      selectedId: null,
      bill: null,
      formOpen: false,
      editCard: null,
      deleteDialog: { open: false, loading: false, item: null },
    }
  },
  computed: {
    usagePercentRaw() {
      if (!this.bill || !this.bill.credit_card) return 0
      const lim = Number(this.bill.credit_card.limit) || 0
      if (lim <= 0) return 0
      const u = Number(this.bill.utilizado_no_cartao) || 0
      return Math.min(100, (u / lim) * 100)
    },
    usagePercentCapped() {
      return Math.min(100, Math.round(this.usagePercentRaw * 10) / 10)
    },
    usagePercentDisplay() {
      return Math.round(this.usagePercentRaw * 10) / 10
    },
    /** ok &lt; 70%, warning 70–89%, danger ≥ 90% ou sem limite disponível */
    usageRisk() {
      const p = this.usagePercentRaw
      if (p >= 90 || (this.bill && Number(this.bill.limite_disponivel) <= 0 && p > 0)) {
        return {
          level: 'danger',
          textClass: 'error--text',
          fillClass: 'cc-usage__fill--danger',
          message: 'Limite praticamente esgotado. Evite novas compras ou antecipe pagamento.',
        }
      }
      if (p >= 70) {
        return {
          level: 'warning',
          textClass: 'amber darken-3--text',
          fillClass: 'cc-usage__fill--warning',
          message: 'Você já usou boa parte do limite. Atenção às próximas despesas.',
        }
      }
      return {
        level: 'ok',
        textClass: 'secondary--text',
        fillClass: 'cc-usage__fill--ok',
        message: '',
      }
    },
    purchaseCount() {
      if (!this.bill || !this.bill.transacoes) return 0
      return this.bill.transacoes.length
    },
  },
  watch: {
    refreshKey() {
      this.loadCards()
    },
  },
  mounted() {
    this.loadCards()
  },
  methods: {
    formatBRL(v) {
      return this.$formatCurrencyBRL(v)
    },
    formatPeriod(p) {
      if (!p || !p.start || !p.end) return ''
      const a = p.start.split('-').reverse().join('/')
      const b = p.end.split('-').reverse().join('/')
      return `${a} — ${b}`
    },
    formatPurchaseDate(iso) {
      if (!iso) return '—'
      const [y, m, d] = String(iso).split('-')
      if (!d || !m) return iso
      return `${d}/${m}/${y}`
    },
    async loadCards() {
      this.loadingList = true
      try {
        const { data } = await axios.get(`${this.apiBase}/credit-cards`)
        this.cards = data.data || []
        if (this.selectedId) {
          const still = this.cards.find((c) => c.id === this.selectedId)
          if (still) await this.loadBill(still.id)
          else {
            this.selectedId = null
            this.bill = null
          }
        }
      } catch (e) {
        this.cards = []
        this.$emit('error', 'Não foi possível carregar os cartões.')
      } finally {
        this.loadingList = false
      }
    },
    async selectCard(c) {
      this.selectedId = c.id
      await this.loadBill(c.id)
    },
    async loadBill(id) {
      this.billLoading = true
      try {
        const { data } = await axios.get(`${this.apiBase}/credit-cards/${id}/bill`)
        this.bill = data
      } catch (e) {
        this.bill = null
        this.$emit('error', 'Não foi possível carregar a fatura.')
      } finally {
        this.billLoading = false
      }
    },
    openCreate() {
      this.editCard = null
      this.formOpen = true
    },
    openEdit(c) {
      this.editCard = { ...c }
      this.formOpen = true
    },
    onCardSaved() {
      this.$emit('saved')
      this.loadCards()
    },
    askDelete(c) {
      this.deleteDialog.item = c
      this.deleteDialog.open = true
    },
    async confirmDelete() {
      if (!this.deleteDialog.item) return
      this.deleteDialog.loading = true
      try {
        await axios.delete(`${this.apiBase}/credit-cards/${this.deleteDialog.item.id}`)
        if (this.selectedId === this.deleteDialog.item.id) {
          this.selectedId = null
          this.bill = null
        }
        this.deleteDialog.open = false
        this.$emit('saved')
        await this.loadCards()
      } catch (e) {
        this.$emit('error', 'Não foi possível excluir o cartão.')
      } finally {
        this.deleteDialog.loading = false
        this.deleteDialog.item = null
      }
    },
  },
}
</script>

<style scoped>
.cc-panel {
  max-width: 960px;
  margin-left: auto;
  margin-right: auto;
}

.cc-panel__btn-new {
  font-weight: 600;
  letter-spacing: 0.02em;
}

.gap-2 > * {
  margin-right: 8px;
  margin-bottom: 8px;
}

.cc-panel__placeholder {
  border-style: dashed !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
}

.cc-panel__detail {
  position: relative;
}

/* Faixa superior “cartão físico” */
.cc-card-hero {
  background: linear-gradient(135deg, #2c2f36 0%, #353942 40%, #3d424d 100%);
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.cc-card-hero__name {
  letter-spacing: 0.02em;
}

.min-w-0 {
  min-width: 0;
}

.cc-panel__body {
  border-top: none !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  background: transparent;
}

.cc-stat__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: rgba(255, 255, 255, 0.55);
  margin-bottom: 4px;
}

.cc-stat__value {
  font-size: 1.1rem;
  font-weight: 600;
  line-height: 1.3;
}

.cc-stat__value--used {
  color: #ff0000 !important;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
}

/* Barra de uso custom (mais “app banco” que v-progress-linear) */
.cc-usage__track {
  height: 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.1);
  overflow: hidden;
}

.cc-usage__fill {
  height: 100%;
  border-radius: 999px;
  transition: width 0.45s ease, background 0.3s ease;
}

.cc-usage__fill--ok {
  background: linear-gradient(90deg, #4caf50, #81c784);
}

.cc-usage__fill--warning {
  background: linear-gradient(90deg, #ffb300, #ffca28);
}

.cc-usage__fill--danger {
  background: linear-gradient(90deg, #d50000, #ff0000);
}

/* Bloco fatura */
.cc-fatura {
  background: linear-gradient(145deg, #3d2020 0%, #353942 55%, #2c2f36 100%);
  color: #fff;
  border: 1px solid rgba(255, 0, 0, 0.25);
  box-shadow: 0 12px 36px rgba(0, 0, 0, 0.45);
}

.cc-fatura__kicker {
  letter-spacing: 0.12em;
  opacity: 0.95;
}

.cc-fatura__amount {
  font-size: clamp(1.75rem, 6vw, 2.35rem);
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -0.02em;
}

/* Lista compras */
.cc-purchases-list {
  background: transparent !important;
}

.cc-purchases-empty {
  background: rgba(0, 0, 0, 0.18) !important;
  border: 1px dashed rgba(255, 255, 255, 0.12);
}

.cc-purchase-item {
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(0, 0, 0, 0.2);
}

.cc-purchase-item__title {
  color: rgba(255, 255, 255, 0.92);
}

.cc-picker-item {
  border: 1px solid transparent;
  transition: background 0.2s ease, border-color 0.2s ease;
}

.cc-picker-item--active {
  background: rgba(255, 0, 0, 0.15) !important;
  border-color: rgba(255, 0, 0, 0.4) !important;
}

.border-b {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.font-weight-semibold {
  font-weight: 600 !important;
}

.cc-panel__sidebar {
  border-color: rgba(255, 255, 255, 0.1) !important;
  position: sticky;
  top: 72px;
}

@media (max-width: 1263px) {
  .cc-panel__sidebar {
    position: static;
  }
}
</style>
