# Base CMS (Laravel)

Painel administrativo (CMS) neutro e reutilizável.
Inclui:

- Autenticação (sessão web) e painel AdminLTE
- Grupos, permissões por módulo e utilizadores
- Páginas, blog (categorias, posts, galeria), clientes e configurações globais
- Roadmap de modularização: [`app/Modules/README.md`](app/Modules/README.md)

## RODAR PROJETO

Clone o repositório e siga os seguintes passos:

- `$ npm i`
- `$ composer install`
- Altere o nome dos containers no arquivo `docker-compose.yml`. (cms_base para o nome do seu projeto)
- `$ docker-compose up`
  - Caso ocorra o erro de network do Docker, basta executar o comando que ele sugerir para criar a network manualmente e após isso rodar o `$ docker-compose up` novamente.
  - Fique atento aos logs que aparecerem no terminal, talvez ocorra erros relativos ao `node-sass`. Acesse o container principal e rode `$ npm rebuild node-sass`.
- Altere os dados do banco de dados do arquivo `.env.example` para os dados que estão no arquivo `docker-compose.yml`. (Fazendo isso caso alguém vá mexer no projeto o `.env.example` já terá as informações corretas e não será necessário fazer alterações)
- Copie o arquivo `.env.example` para `.env` (não é necessário fazer nenhuma alteração)
- `$ docker exec -it (ID do container CMS) /bin/bash`

Dentro do container rode os seguintes comandos:

- `$ php artisan key:generate`
- `$ php artisan migrate`
- `$ php artisan db:seed`
- `$ php artisan storage:link`
- `$ php artisan jwt:secret`

## DEV

O Docker por padrão deverá rodar o `$ npm run dev` e o `$ php artisan serve`.
Para rodar os comandos _make_ do **Laravel** faça isso fora do Docker para evitar problemas de permissões de arquivos.

## CONTRIBUIR

Escolha um **_issue_**, crie uma **_branch_** e submeta um **_pull request_** para a **_master_**.
