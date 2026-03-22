# Base CMS (Laravel)

## Como rodar o projeto

### 1. Docker

Na raiz do repositório:

```bash
docker compose up -d --build
```

### Copie o arquivo .env

```bash
cp .env.example .env
```

### 2. Entrar no container da aplicação

```bash
docker exec -it nova_base bash
```

### 3. Dependências PHP (dentro do container)

```bash
composer install
```

### 4. Laravel (Artisan) — dentro do container

Exemplos usuais após o primeiro setup:

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

**Reset completo com dados de demonstração (finanças):**

```bash
php artisan migrate:fresh --seed
```

Após o seed: utilizador **`test@test.com`** / **`123456`** (grupo administrador, categorias e transações de exemplo). Também existe **`admin@example.com`** / **`123456`**.

Outros comandos do Artisan, quando precisar: `php artisan <comando>`.

### Formatação PHP (Pint)

Na raiz do projeto (host ou container, com Composer instalado):

```bash
vendor/bin/pint
vendor/bin/pint --dirty
```

### Testes automatizados (PHPUnit)

Na raiz do projeto (host ou container), com dependências instaladas (`composer install`):

```bash
# Toda a suíte
./vendor/bin/phpunit

# Equivalente (chama o mesmo binário)
php ./vendor/bin/phpunit
```

**Por pasta ou ficheiro:**

```bash
# Só testes unitários
./vendor/bin/phpunit tests/Unit

# Só finanças (Feature)
./vendor/bin/phpunit tests/Feature/Finance

# Um ficheiro específico
./vendor/bin/phpunit tests/Feature/Finance/TransactionApiTest.php

# Filtrar por nome do método (substring)
./vendor/bin/phpunit --filter test_store_transaction
```

**PHP com extensão PDO MySQL:** os testes Feature usam base de dados (`RefreshDatabase`). Se o `php` padrão do sistema não tiver `pdo_mysql` (erro `could not find driver`), use a versão correta, por exemplo:

```bash
php8.2 ./vendor/bin/phpunit
```

**Configuração de BD para testes** (host Docker, `DB_HOST`, etc.): [docs/testing.md](docs/testing.md).

### 5. Front-end (no host, na raiz do projeto)

Instalação única:

```bash
npm i
```

**Desenvolvimento (HMR)** — recomendado enquanto altera Vue/SCSS:

```bash
npm run dev
```

**Build de produção** (gera assets em `public/build/`, usados pelo `@vite` nas views):

```bash
npm run build
```

**Bundles relevantes:**

| Entrada Vite                                 | Uso                                         |
| -------------------------------------------- | ------------------------------------------- |
| `resources/assets/js/cms/auth-app.js`        | Login, cadastro, esqueci senha, reset (CMS) |
| `resources/assets/js/finance/finance-app.js` | SPA Finanças (dashboard, onboarding, etc.)  |

Se o CMS ou as finanças não refletirem alterações de JS/CSS, rode `npm run dev` ou `npm run build` conforme o ambiente.

---

**Nota:** O serviço PHP do compose chama-se `nova_base` (`container_name: nova_base`). Se alterar o nome no `docker-compose.yml`, ajuste o comando `docker exec`.
