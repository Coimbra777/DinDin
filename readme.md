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
php artisan jwt:secret
```

**Reset completo com dados de demonstração (finanças):**

```bash
php artisan migrate:fresh --seed
```

Após o seed: utilizador **`test@test.com`** / **`123456`** (grupo administrador, categorias e transações de exemplo). Também existe **`admin@example.com`** / **`123456`**.

Outros comandos do Artisan, quando precisar: `php artisan <comando>`.

### Testes automatizados (PHPUnit)

```bash
./vendor/bin/phpunit
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Feature/Finance
```

Requisitos e `.env.testing`: ver [docs/testing.md](docs/testing.md).

### 5. Front-end (fora do container)

No host, na raiz do projeto:

```bash
npm i
npm run dev
```

---

**Nota:** O serviço PHP do compose chama-se `nova_base` (`container_name: nova_base`). Se alterar o nome no `docker-compose.yml`, ajuste o comando `docker exec`.
