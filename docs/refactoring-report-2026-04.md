# Relatório de refatoração (qualidade / consistência)

**Data:** 2026-04  
**Objetivo:** Reduzir duplicação e magic strings sem alterar regras de negócio nem contratos JSON da API.

---

## 1. Código removido

- **Nenhum ficheiro ou classe apagado.** Não foi identificado código morto com cadeia de referências verificável em toda a árvore (além do escopo desta passagem). Itens só referenciados por comentários (ex.: `config/acl.php` apenas comentado no `AuthServiceProvider`) mantêm-se como documentação viva.

---

## 2. Código refatorado

| Área | Alteração |
|------|-----------|
| **PHP – duplicação** | Novo `App\Services\Finance\TransactionDuplicateGuard` com `hasChildInCalendarMonth()` e `existsChildForYearMonth()`. `TransactionApiService` e `RecurringTransactionService` deixam de duplicar a query Eloquent. |
| **PHP – constantes** | `FinancialSummaryService::totalsByCategoryForUser()` passa a usar `selectRaw` com placeholders e `Transaction::TYPE_INCOME` / `TYPE_EXPENSE`, alinhado aos restantes agregados do mesmo serviço. |
| **Vue – pagamento / tipo** | Novo `resources/assets/js/finance/transactionPaymentUi.js` com funções puras (status efetivo, labels, cores Vuetify, ícone/avatar por tipo). `TransactionList.vue` delega para este módulo. |
| **Vue – FinanceApp** | Uso de `PAYMENT_STATUS_*` e `TRANSACTION_TYPE_EXPENSE` importados de `transactionTypes.js`; filtro de pagamento, `markTransactionPaid` e query `type` alinhados a constantes. |

---

## 3. Duplicações eliminadas

- Query “filho no mês civil por `parent_transaction_id`” (antes em dois serviços).
- Lógica de apresentação de pagamento no `TransactionList` (centralizada em `transactionPaymentUi.js`).
- Strings literais `income`/`expense` em agregado por categoria no `FinancialSummaryService` (substituídas por constantes do model).

---

## 4. Melhorias de performance

- **Nenhuma alteração propositada de query volume ou eager loading** nesta passagem (comportamento de SQL equivalente; `totalsByCategoryForUser` mantém o mesmo plano lógico).

---

## 5. Testes

- `tests/Unit/Services/Finance/TransactionExpenseRulesTest.php` continuou a passar após alterações.
- Testes de integração com BD não foram executados neste ambiente (driver indisponível); recomenda-se `./vendor/bin/phpunit` no ambiente com migrações aplicadas.

---

## 6. Melhorias futuras (não aplicadas)

- **Inventário de código morto:** varrimento com análise estática (ex. PHPStan unused symbols) + cobertura de rotas chamadas pela SPA.
- **N+1:** rever listagens que carreguem `category` de forma inconsistente após novos endpoints.
- **`Transaction` model:** `scopeFilter` com regras de `payment_status` continua no model (aceitável como scope); extrair para query object só se crescer mais.
- **`config/acl.php`:** passar a ser lido em runtime (ou fundir documentação noutro sítio) se quiser uma única fonte de verdade além de `GateNames`.

---

## 7. Segurança e API

- Contratos JSON da API **inalterados** (mesmos nomes de chaves e semântica).
- Autorização e ownership **não** foram modificados nesta passagem.
