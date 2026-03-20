# Testes automatizados (Finanças)

## Requisitos

- **Feature** (`tests/Feature/Finance/*`): usam `RefreshDatabase` e precisam de uma base de dados acessível (MySQL/MariaDB como no `.env`, ou SQLite em memória).
- **Unit** (`tests/Unit/*`): a maioria não toca na BD; podem correr sem servidor de BD.

### SQLite em memória (opcional)

1. Instale a extensão PHP: `pdo_sqlite`.
2. No `phpunit.xml`, descomente:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### MySQL / MariaDB

Garanta que o serviço está a correr (ex.: `docker compose up -d`) e que `DB_*` no `.env` ou `.env.testing` aponta para uma base de **teste** dedicada — `migrate:fresh` apaga dados nessa base.

**Modelo:** copie `.env.testing.example` para `.env.testing` e crie a base (ex. `nova_base_test`). O ficheiro `.env.testing` está no `.gitignore`.

### Dados QA via `db:seed` completo

No `.env` ou `.env.testing`, defina `SEED_FINANCIAL_TEST_DATA=true` e corra:

```bash
php artisan db:seed
```

Isto executa os seeders habituais e, no fim, o `FinancialTestDataSeeder` (utilizador QA, 55+ transações, etc.). Sem a variável, o seeder volumoso **não** corre.

## Comandos

```bash
# Toda a suíte
./vendor/bin/phpunit

# Só unitários
./vendor/bin/phpunit tests/Unit

# Só API finanças
./vendor/bin/phpunit tests/Feature/Finance
```

## Utilizador padrão após `migrate:fresh --seed`

| Email | Senha | Notas |
|-------|-------|--------|
| `test@test.com` | `123456` | Dados de finanças (transações, metas, cartões, planejamento) |
| `admin@example.com` | `123456` | Administrador CMS |

## Dados de seed para QA (opcional)

```bash
php artisan db:seed --class=FinancialTestDataSeeder
```

Cria utilizador `finance-qa@example.test` (password padrão da factory: `password`), cartões, metas, planejamentos e 55+ transações. O seeder garante grupo, módulo `finance` e ligação `group_module` se faltarem.

## Mocks (HTTP)

Para isolar chamadas HTTP externas, use `Illuminate\Support\Facades\Http::fake()` nos testes que precisem; não é necessário `GuzzleHttp\Psr7\Response` se usar apenas `Http::response()` com o stack do Laravel (Guzzle incluído via `illuminate/http`).
