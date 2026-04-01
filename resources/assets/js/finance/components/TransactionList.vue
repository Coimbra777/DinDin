<template>
  <v-card class="tx-list rounded-xl" flat outlined>
    <v-card-text
      class="pb-2 pt-4 px-3 px-sm-4 d-flex align-start align-sm-center flex-wrap"
    >
      <div class="d-flex align-center min-w-0 mb-2 mb-sm-0">
        <v-icon
          :size="$vuetify.breakpoint.xs ? 22 : 24"
          color="secondary"
          class="mr-2 flex-shrink-0"
        >
          mdi-format-list-bulleted
        </v-icon>
        <div class="min-w-0">
          <div
            class="subtitle-1 font-weight-bold text-truncate tx-list__heading"
          >
            {{ title }}
          </div>
          <div v-if="subtitle" class="text-caption secondary--text">
            {{ subtitle }}
          </div>
        </div>
      </div>
      <v-spacer class="d-none d-sm-block" />
      <v-progress-circular
        v-if="loading"
        indeterminate
        size="22"
        width="2"
        color="primary"
        class="flex-shrink-0"
      />
    </v-card-text>

    <template v-if="!loading && items.length === 0">
      <v-card-text class="text-center finance-text-muted py-10 px-4">
        <v-icon size="44" color="secondary">mdi-inbox-outline</v-icon>
        <p class="mt-3 mb-0 body-2">Nenhuma transação neste período.</p>
      </v-card-text>
    </template>

    <v-list
      v-else-if="!loading"
      class="py-0 px-2 px-sm-3 pb-3"
      :dense="layout === 'compact'"
      :nav="layout === 'comfortable'"
      color="transparent"
    >
      <template v-for="(item, i) in items">
        <v-list-item
          :key="item.id"
          :class="[
            'tx-list__item rounded-lg mb-1',
            { 'tx-list__item--comfortable': layout === 'comfortable' },
          ]"
          :ripple="false"
        >
          <v-list-item-avatar
            :size="layout === 'comfortable' ? 48 : 40"
            class="my-2 mx-0 mr-3"
          >
            <v-avatar
              :color="avatarColor(item)"
              :size="layout === 'comfortable' ? 48 : 40"
            >
              <v-icon color="white" :size="layout === 'comfortable' ? 24 : 20">
                {{ itemIcon(item) }}
              </v-icon>
            </v-avatar>
          </v-list-item-avatar>

          <v-list-item-content class="py-2">
            <v-list-item-title
              class="tx-list__title font-weight-medium text-body-1 tx-list__heading d-flex align-center flex-wrap"
            >
              <span class="text-truncate">{{ item.title }}</span>
              <v-chip
                v-if="item.is_legacy_recurring || item.recurring_transaction_id"
                x-small
                outlined
                color="deep-purple"
                class="ml-2 flex-shrink-0"
                label
              >
                Recorrente (legado)
              </v-chip>
              <v-chip
                v-if="
                  item.installment_number != null && item.installment_of != null
                "
                x-small
                outlined
                color="teal"
                class="ml-2 flex-shrink-0"
                label
              >
                {{ item.installment_number }}/{{ item.installment_of }} parcelas
              </v-chip>
              <v-chip
                v-if="isExpense(item) && resolvePaymentStatus(item)"
                x-small
                dark
                :color="paymentStatusChipColor(item)"
                class="ml-2 flex-shrink-0"
                label
              >
                {{ paymentStatusLabel(item) }}
              </v-chip>
              <v-chip
                v-if="isExpense(item) && item.is_recurring"
                x-small
                outlined
                color="indigo"
                class="ml-2 flex-shrink-0"
                label
              >
                Recorrente
              </v-chip>
            </v-list-item-title>
            <v-list-item-subtitle
              class="tx-list__meta text-caption secondary--text mt-1"
            >
              <span class="tx-list__meta-icon">
                <v-icon x-small class="mr-1" color="secondary"
                  >mdi-calendar-outline</v-icon
                >
                {{ formatTxDate(item.transaction_date) }}
              </span>
              <template v-if="item.category">
                <span class="mx-1">·</span>
                <span
                  class="text-truncate d-inline-block"
                  style="max-width: 42vw"
                >
                  {{ item.category.name }}
                </span>
              </template>
            </v-list-item-subtitle>
            <div
              v-if="isExpense(item) && item.due_date"
              class="text-caption mt-1 d-flex align-center flex-wrap"
            >
              <v-icon x-small class="mr-1" :color="dueMetaColor(item)"
                >mdi-calendar-clock</v-icon
              >
              <span :class="dueMetaColor(item) + '--text'"
                >Venc. {{ formatDueDayMonth(item.due_date) }}</span
              >
              <span
                v-if="overdueDaysHint(item)"
                class="ml-2 error--text font-weight-medium"
              >
                {{ overdueDaysHint(item) }}
              </span>
            </div>
            <div
              v-if="
                showActions &&
                isExpense(item) &&
                !isPaidStatus(item)
              "
              class="mt-2 d-flex flex-wrap align-center"
            >
              <v-btn
                type="button"
                small
                depressed
                color="success"
                class="text-none"
                :loading="isMarkingPaid(item)"
                :disabled="isMarkingPaid(item)"
                @click.stop="onMarkPaidClick(item)"
              >
                <v-icon left small>mdi-check</v-icon>
                Marcar como pago
              </v-btn>
            </div>
            <div
              v-if="item.description"
              class="text-caption finance-text-muted text-truncate mt-1 d-none d-sm-block"
            >
              {{ item.description }}
            </div>
          </v-list-item-content>

          <v-list-item-action class="my-2 align-self-center">
            <div
              class="tx-list__amount font-weight-bold tabular-nums text-body-1"
              :class="
                item.type === 'income'
                  ? 'tx-list__amount--in'
                  : 'tx-list__amount--out'
              "
            >
              {{ formatAmountLine(item) }}
            </div>
          </v-list-item-action>

          <v-list-item-action v-if="showActions" class="flex-row mx-0 my-2">
            <v-btn
              icon
              small
              aria-label="Duplicar nos próximos meses"
              type="button"
              @click.stop="onDuplicateClick(item)"
            >
              <v-icon small color="secondary">mdi-content-copy</v-icon>
            </v-btn>
            <v-btn icon small aria-label="Editar" @click="$emit('edit', item)">
              <v-icon small color="secondary">mdi-pencil-outline</v-icon>
            </v-btn>
            <v-btn
              icon
              small
              aria-label="Excluir"
              @click="$emit('delete', item)"
            >
              <v-icon small color="secondary">mdi-delete-outline</v-icon>
            </v-btn>
          </v-list-item-action>
        </v-list-item>
        <v-divider
          v-if="i < items.length - 1"
          :key="'d' + item.id"
          class="mx-2 mx-sm-3 opacity-35"
        />
      </template>
    </v-list>
  </v-card>
</template>

<script>
import { formatCurrencyBRL } from "../currency";
import {
  formatDatePtBR,
  formatDayMonthPtBR,
  wholeDaysPastDue,
} from "../format";
import {
  PAYMENT_STATUS_OVERDUE,
  PAYMENT_STATUS_PAID,
  PAYMENT_STATUS_PENDING,
  TRANSACTION_TYPE_EXPENSE,
} from "../transactionTypes";

export default {
  name: "TransactionList",
  props: {
    title: { type: String, default: "Transações" },
    /** Linha secundária sob o título (ex.: mês) */
    subtitle: { type: String, default: "" },
    items: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    showActions: { type: Boolean, default: true },
    /** ids com POST mark-as-paid em curso */
    markingPaidById: { type: Object, default: () => ({}) },
    /** comfortable = mais ar no mobile; compact = denso */
    layout: {
      type: String,
      default: "compact",
      validator: (v) => ["compact", "comfortable"].includes(v),
    },
  },
  methods: {
    isExpense(item) {
      return item && item.type === TRANSACTION_TYPE_EXPENSE;
    },
    resolvePaymentStatus(item) {
      if (!this.isExpense(item)) return "";
      if (item.payment_status) return item.payment_status;
      if (item.is_overdue) return PAYMENT_STATUS_OVERDUE;
      return PAYMENT_STATUS_PENDING;
    },
    isPaidStatus(item) {
      if (!this.isExpense(item)) return false;
      return this.resolvePaymentStatus(item) === PAYMENT_STATUS_PAID;
    },
    paymentStatusLabel(item) {
      if (!this.isExpense(item)) return "";
      const s = this.resolvePaymentStatus(item);
      if (s === PAYMENT_STATUS_PAID) return "Pago";
      if (s === PAYMENT_STATUS_OVERDUE) return "Atrasado";
      return "Pendente";
    },
    paymentStatusChipColor(item) {
      if (!this.isExpense(item)) return "secondary";
      const s = this.resolvePaymentStatus(item);
      if (s === PAYMENT_STATUS_PAID) return "success";
      if (s === PAYMENT_STATUS_OVERDUE) return "error";
      return "warning";
    },
    dueMetaColor(item) {
      if (!this.isExpense(item)) return "secondary";
      return this.resolvePaymentStatus(item) === PAYMENT_STATUS_OVERDUE
        ? "error"
        : "secondary";
    },
    formatDueDayMonth(iso) {
      return formatDayMonthPtBR(iso);
    },
    overdueDaysHint(item) {
      if (!this.isExpense(item) || !item.due_date || this.isPaidStatus(item)) {
        return "";
      }
      const n = wholeDaysPastDue(item.due_date);
      if (n <= 0) return "";
      return n === 1 ? "Atrasado há 1 dia" : `Atrasado há ${n} dias`;
    },
    isMarkingPaid(item) {
      return !!(this.markingPaidById && this.markingPaidById[item.id]);
    },
    onMarkPaidClick(item) {
      if (!this.isExpense(item)) return;
      this.$emit("mark-paid", item);
    },
    onDuplicateClick(transaction) {
      this.$emit("duplicate", transaction);
    },
    formatTxDate(iso) {
      return formatDatePtBR(iso);
    },
    itemIcon(item) {
      if (item.type === "income") return "mdi-cash-plus";
      return "mdi-cash-minus";
    },
    avatarColor(item) {
      if (item.type === "income") return "success";
      return "error";
    },
    /** Valor sempre em R$ com sinal visível */
    formatAmountLine(item) {
      const base = formatCurrencyBRL(item.amount);
      return item.type === "income" ? `+ ${base}` : `− ${base}`;
    },
  },
};
</script>

<style scoped>
.min-w-0 {
  min-width: 0;
}

.tx-list__item {
  min-height: 56px;
  border: 1px solid transparent;
  transition: background 0.2s ease;
}
.tx-list__item--comfortable {
  min-height: 64px;
}

.tx-list__title {
  line-height: 1.35;
}

.tx-list__meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  line-height: 1.4;
}
.tx-list__meta-icon {
  display: inline-flex;
  align-items: center;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
}

.tx-list__amount--in {
  color: #4caf50 !important;
}
.tx-list__amount--out {
  color: #ff0000 !important;
}

.opacity-35 {
  opacity: 0.35;
}
</style>
