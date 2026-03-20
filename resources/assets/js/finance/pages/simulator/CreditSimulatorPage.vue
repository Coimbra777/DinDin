<template>
  <div class="simulator-page px-1">
    <p class="text-caption secondary--text mb-4">
      Simule parcelamento no cartão (valor por parcela e impacto mensal aproximado). Não inclui análise de juros compostos.
    </p>

    <v-row>
      <v-col cols="12" md="5">
        <v-card class="rounded-xl" flat outlined>
          <v-card-title class="subtitle-1 font-weight-bold">
            <v-icon left color="primary">mdi-calculator-variant</v-icon>
            Dados
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-text-field
              v-model.number="form.amount"
              label="Valor total (R$)"
              type="number"
              step="0.01"
              min="0.01"
              outlined
              dense
              hide-details="auto"
              prepend-inner-icon="mdi-cash"
              class="mb-3"
            />
            <v-text-field
              v-model.number="form.installments"
              label="Número de parcelas"
              type="number"
              min="2"
              max="60"
              outlined
              dense
              hide-details="auto"
              prepend-inner-icon="mdi-numeric"
              class="mb-3"
            />
            <v-text-field
              v-model.number="form.interest_percent_total"
              label="Juros sobre o total (%) — opcional"
              type="number"
              step="0.01"
              min="0"
              outlined
              dense
              hide-details="auto"
              prepend-inner-icon="mdi-percent-outline"
              class="mb-4"
            />
            <v-btn
              color="primary"
              depressed
              block
              large
              rounded
              :loading="loading"
              @click="simulate"
            >
              Simular
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="7">
        <v-card v-if="result" class="rounded-xl h-100" flat outlined>
          <v-card-title class="subtitle-1 font-weight-bold">Resultado</v-card-title>
          <v-divider />
          <v-card-text>
            <v-simple-table dense class="transparent">
              <tbody>
                <tr>
                  <td class="secondary--text">Valor financiado</td>
                  <td class="text-right font-weight-medium tabular-nums">{{ formatBRL(result.principal) }}</td>
                </tr>
                <tr>
                  <td class="secondary--text">Parcelas</td>
                  <td class="text-right">{{ result.installments }}</td>
                </tr>
                <tr>
                  <td class="secondary--text">Valor da parcela</td>
                  <td class="text-right font-weight-bold finance-amount-expense tabular-nums">
                    {{ formatBRL(result.installment_value) }}
                  </td>
                </tr>
                <tr>
                  <td class="secondary--text">Total a pagar</td>
                  <td class="text-right font-weight-medium tabular-nums">{{ formatBRL(result.total_repayment) }}</td>
                </tr>
                <tr v-if="result.interest_percent_total_applied > 0">
                  <td class="secondary--text">Juros (% no total)</td>
                  <td class="text-right">{{ result.interest_percent_total_applied }}%</td>
                </tr>
              </tbody>
            </v-simple-table>
            <v-alert v-if="result.monthly_impact" type="info" text dense class="mt-4 mb-0 text-body-2">
              {{ result.monthly_impact.observacao }}
            </v-alert>
          </v-card-text>
        </v-card>
        <v-card v-else class="rounded-xl pa-8 text-center finance-text-muted" flat outlined>
          Preencha os dados e clique em <strong>Simular</strong>.
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CreditSimulatorPage',
  props: {
    apiBase: { type: String, required: true },
    refreshKey: { type: Number, default: 0 },
  },
  data() {
    return {
      loading: false,
      form: {
        amount: null,
        installments: 12,
        interest_percent_total: 0,
      },
      result: null,
    }
  },
  watch: {
    refreshKey() {
      this.result = null
    },
  },
  methods: {
    formatBRL(v) {
      return this.$formatCurrencyBRL(v)
    },
    async simulate() {
      const base = (this.apiBase || '').replace(/\/$/, '')
      if (!base) return
      const amount = Number(this.form.amount)
      const inst = parseInt(this.form.installments, 10)
      if (!amount || amount < 0.01) {
        this.$emit('error', 'Informe um valor válido.')
        return
      }
      if (!inst || inst < 2) {
        this.$emit('error', 'Use pelo menos 2 parcelas.')
        return
      }
      this.loading = true
      try {
        const payload = {
          amount,
          installments: inst,
        }
        const ip = Number(this.form.interest_percent_total)
        if (ip > 0) {
          payload.interest_percent_total = ip
        }
        const { data } = await axios.post(`${base}/credit-simulator/simulate`, payload)
        this.result = data
      } catch (e) {
        this.result = null
        const msg = (e.response && e.response.data && e.response.data.message) || 'Não foi possível simular.'
        this.$emit('error', typeof msg === 'string' ? msg : 'Não foi possível simular.')
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.simulator-page {
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}
.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
