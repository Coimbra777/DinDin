<template>
  <v-dialog
    :value="value"
    max-width="520"
    scrollable
    content-class="finance-dialog-content"
    @input="$emit('input', $event)"
  >
    <v-card>
      <v-card-title class="headline font-weight-bold primary white--text py-4">
        <v-icon left dark>mdi-lifebuoy</v-icon>
        Como funciona?
        <v-spacer />
        <v-btn icon dark aria-label="Fechar" @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-6 pb-2">
        <section class="mb-6">
          <h3 class="subtitle-1 font-weight-bold mb-2 d-flex align-center">
            <v-icon left small color="primary">mdi-shape-outline</v-icon>
            Categorias
          </h3>
          <p class="text-body-2 finance-help-text mb-0">
            As categorias organizam suas movimentações (ex.: Alimentação, Salário). Cada categoria é de
            <strong>receita</strong> ou <strong>despesa</strong> — isso define como o valor entra nos totais e
            relatórios.
          </p>
        </section>

        <section class="mb-6">
          <h3 class="subtitle-1 font-weight-bold mb-2 d-flex align-center">
            <v-icon left small color="primary">mdi-bank-transfer</v-icon>
            Transações
          </h3>
          <p class="text-body-2 finance-help-text mb-0">
            Uma <strong>transação</strong> é cada entrada ou saída de dinheiro: você escolhe se é receita ou despesa,
            informa valor, data e (opcionalmente) categoria e cartão. O painel e o mês selecionado mostram o resumo do
            que entrou e saiu.
          </p>
        </section>

        <section class="mb-2">
          <h3 class="subtitle-1 font-weight-bold mb-2 d-flex align-center">
            <v-icon left small color="primary">mdi-view-dashboard-outline</v-icon>
            O sistema
          </h3>
          <ul class="text-body-2 finance-help-text pl-4 mb-0">
            <li class="mb-2"><strong>Dashboard</strong> — visão do mês e indicadores principais.</li>
            <li class="mb-2"><strong>Transações</strong> — lista, filtros e inclusão/edição de lançamentos.</li>
            <li class="mb-2"><strong>Categorias</strong> — criar e editar grupos para classificar receitas e despesas.</li>
            <li class="mb-2"><strong>Cartões</strong> — cadastrar cartões; em despesas, você pode vincular à fatura.</li>
            <li class="mb-0">Demais telas (projeção, relatórios, alertas, etc.) aprofundam análise e planejamento.</li>
          </ul>
        </section>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn color="primary" depressed rounded @click="close">Entendi</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
export default {
  name: 'FinanceHelpModal',
  props: {
    value: { type: Boolean, default: false },
  },
  methods: {
    close() {
      this.$emit('input', false)
    },
  },
}
</script>

<style scoped>
.finance-help-text {
  line-height: 1.55;
  opacity: 0.92;
}
</style>
