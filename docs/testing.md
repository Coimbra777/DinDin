# Testes automatizados (Finanças)

## Requisitos

- **Feature** (`tests/Feature/Finance/*`): usam `RefreshDatabase` e precisam de uma base de dados acessível (MySQL/MariaDB como no `.env`, ou SQLite em memória).
- **Unit** (`tests/Unit/*`): a maioria não toca na BD; podem correr sem servidor de BD.

### Erro: `could not find driver` (Connection: mysql)

O PHPUnit usa o mesmo PHP da linha de comandos. Se aparecer **`PDOException: could not find driver`** ao correr testes com `RefreshDatabase`, o PHP **não tem o driver PDO MySQL** (`pdo_mysql`).

**Correção (Ubuntu/Debian)** — ajuste a versão do PHP à do `php -v`:

```bash
sudo apt install php8.3-mysql
php -m | grep -i pdo
```

Deve listar `pdo_mysql`. Alternativas: correr `./vendor/bin/phpunit` **dentro do container Docker** do projeto (onde a extensão costuma existir), ou usar **SQLite** para testes (secção seguinte).

### Erro: `getaddrinfo for … failed` / hostname Docker (`mysql_*`)

Se o `.env` usa `DB_HOST=mysql_nova_base` (ou outro nome de **serviço Compose**), esse nome **só resolve dentro da rede Docker**. Na máquina host, o PHPUnit falha ao ligar à BD antes de qualquer asserção (muitos erros, zero assertions).

**Correção (escolha uma):**

1. **Recomendado:** copie `.env.testing.example` para `.env.testing` e defina `DB_HOST=127.0.0.1` (ou o host onde o MySQL está exposto), mais `DB_DATABASE` de teste dedicada. O Laravel carrega `.env.testing` com `APP_ENV=testing`.
2. Correr `./vendor/bin/phpunit` **no container** da aplicação (mesma rede que o MySQL).
3. Mapear o hostname no host, por exemplo em `/etc/hosts`: `127.0.0.1 mysql_nova_base` (só se a porta MySQL estiver publicada no host).

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

### API finanças nos testes (`/cms/finance/api/*`)

- Pedidos **JSON** sem sessão (`getJson` / `postJson` sem `actingAs`) recebem **401** (middleware `auth`), não 302.
- O índice de transações filtra por **mês civil** (por defeito o mês atual). Factories com datas aleatórias podem cair fora desse mês — em testes que contem linhas, fixe `transaction_date` ou use o query param `month=YYYY-MM` alinhado aos dados.
- `FinanceApiTestCase` faz `Carbon::setTestNow()` no `tearDown` para testes que fixam a data não afetarem os seguintes.

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
| `test@test.com` | `123456` | Dados de finanças (transações, metas, planejamento) |
| `admin@example.com` | `123456` | Administrador CMS |

## Dados de seed para QA (opcional)

```bash
php artisan db:seed --class=FinancialTestDataSeeder
```

Cria utilizador `finance-qa@example.test` (password padrão da factory: `password`), metas, planejamentos e 55+ transações. O seeder garante grupo, módulo `finance` e ligação `group_module` se faltarem.

## Mocks (HTTP)

Para isolar chamadas HTTP externas, use `Illuminate\Support\Facades\Http::fake()` nos testes que precisem; não é necessário `GuzzleHttp\Psr7\Response` se usar apenas `Http::response()` com o stack do Laravel (Guzzle incluído via `illuminate/http`).
