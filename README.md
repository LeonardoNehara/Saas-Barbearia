# Agenda Barbearia - ambiente local

Estrutura inicial Laravel, sem funcionalidades de negocio ou frontend adicional.
Requisitos: Docker Desktop (containers Linux) com Docker Compose v2.
Nao e necessario instalar PHP, Composer ou Node no host.

## Primeiro uso

Na raiz do projeto, copie `.env.example` para `.env` (PowerShell):

```powershell
Copy-Item .env.example .env
```

Depois execute:

```sh
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
```

O primeiro build compila as extensoes PHP e pode levar alguns minutos.
A pagina padrao do Laravel fica disponivel depois da instalacao das dependencias,
geracao da chave e migrations. Nao execute `composer run setup`: o script padrao
inclui npm, desnecessario nesta etapa.

- Laravel: http://localhost:8000
- phpMyAdmin: http://localhost:8080 (usuario `agenda`, senha `agenda`)
- MySQL interno: `mysql:3306`, banco `agenda_barbearia`
- Redis interno: `redis:6379`, cliente PHP `phpredis`

As credenciais do exemplo sao ficticias e exclusivas para desenvolvimento local.
`APP_KEY` fica vazio no exemplo e deve ser gerado individualmente.
As portas HTTP estao restritas ao computador local; MySQL e Redis nao publicam portas.
Sessoes, filas e cache mantem os drivers database padrao do Laravel; Redis esta
instalado e acessivel para uso posterior.

## Comandos de desenvolvimento

```sh
docker compose ps
docker compose exec app php artisan --version
docker compose exec app php -m
docker compose exec app composer check-platform-reqs
docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
docker compose exec nginx nginx -t
docker compose logs --tail=50 app nginx mysql redis
docker compose down
```

Execute Artisan e Composer dentro do container PHP 8.4. O PHP do host pode ser
incompativel com a versao do Laravel.

## Permissoes e persistencia

`docker/php/entrypoint.sh` prepara os diretorios de storage e bootstrap/cache,
com grupo www-data e permissoes de escrita, antes de iniciar o PHP-FPM.
Isso inclui views, cache/data e sessions, evitando o fallback de tempnam para /tmp.
Para compilacao manual de views, use o mesmo usuario do PHP-FPM:

```sh
docker compose exec --user www-data app php artisan view:cache
```

MySQL e Redis usam volumes nomeados do Docker, fora do repositorio.
`docker compose down` preserva os dados. A opcao `down -v` apaga esses volumes.
Alterar as credenciais no .env nao altera usuarios de um MySQL ja inicializado;
nesse caso, ajuste os usuarios no banco existente antes de reiniciar.

`.env`, vendor, node_modules, logs, arquivos temporarios e dados locais nao devem
ser versionados. Mantenha composer.lock e .env.example no controle de versao.

## Estrutura

```text
docker-compose.yml
docker/
  php/
    Dockerfile
    entrypoint.sh
  nginx/
    default.conf
.env.example
.gitignore
database/migrations/
  0001_01_01_000000_create_users_table.php
  0001_01_01_000001_create_cache_table.php
  0001_01_01_000002_create_jobs_table.php
```

Laravel Boost foi instalado como dependencia de desenvolvimento conforme AGENTS.md.
Nenhuma biblioteca de UI foi adicionada.

## Autenticacao, perfis e isolamento por estabelecimento

A base utiliza o guard `web` nativo (sessao) e o provider Eloquent de `User`.
Nao existem endpoints ou telas de login nesta etapa. Ao implementar o login,
usar `Auth::attempt` com `email`, `password` e `active => true`, regenerar a
sessao apos autenticar e aplicar o middleware de status em todas as areas protegidas.

Agrupar futuras rotas autenticadas com `['auth', 'active']` dentro do grupo `web`.
Para areas administrativas, acrescentar `can:is-admin`. O Gate `is-admin`
permite apenas administradores ativos; visitantes e barbeiros sao recusados.
Sem autenticacao, a resposta e JSON 401, inclusive sem o header Accept; usuarios
inativos recebem JSON 403. O middleware verifica o status em cada requisicao,
inclusive de sessoes abertas antes da desativacao. Ele nao e global.

`role` aceita apenas `admin` ou `barbeiro` (enum no banco, string no model),
com default `barbeiro`. `active` tem default true e cast boolean.
`isAdmin()` e `isBarbeiro()` verificam apenas o perfil; o Gate verifica tambem o status.
`role` e `active` nao sao mass assignable. Alteracoes devem ser explicitas e
autorizadas pelo servidor, nunca via `fill($request->all())`.

### Padrao obrigatorio para futuros Controllers e Policies

- Obter o estabelecimento de `$request->user()->estabelecimento_id`, nunca de
  parametros, headers ou payload enviados pelo cliente.
- Em listagens, leituras, alteracoes e exclusoes, iniciar a consulta com
  `Recurso::query()->where('estabelecimento_id', $request->user()->estabelecimento_id)`.
  Para um ID, aplicar `findOrFail($id)` nessa consulta: um recurso de outro
  estabelecimento deve responder 404. Nao usar `Recurso::find($id)` sem filtro.
- Em criacoes, usar a relacao do estabelecimento autenticado para definir a FK
  no servidor. Aceitar apenas campos explicitamente validados e excluir
  `estabelecimento_id`, `role` e `active` de payloads comuns.
- Policies devem verificar novamente a igualdade entre o estabelecimento do
  usuario e o do recurso, alem de status e permissoes da operacao. Negar acesso
  entre estabelecimentos com `Response::denyAsNotFound()`.
- Ser admin nao concede acesso a outro estabelecimento: `is-admin` sozinho
  nao substitui o filtro da consulta nem a Policy do recurso.
- Nao confiar em route model binding sem escopo: resolver o recurso dentro da
  consulta filtrada ou configurar binding explicitamente limitado ao tenant.
- Testar com dois estabelecimentos que listagens nao vazem dados e que leitura,
  alteracao, exclusao e tentativa de forjar a FK nao alcancem o outro tenant.

Esse padrao e explicito: nao ha Global Scope, middleware de tenant ou pacote
de multi-tenancy. As entidades futuras implementarao suas relacoes e Policies.

### Dados locais

`php artisan db:seed` cria ou atualiza os perfis demo somente em `local` ou
`testing`. Ambos pertencem a Barbearia Demo e ficam ativos:

- Admin Demo: `admin@barbearia-demo.test`, perfil `admin`.
- Barbeiro Demo: `barbeiro@barbearia-demo.test`, perfil `barbeiro`.

Senha inicial ficticia de ambos: `demo-local-only`, exclusiva de desenvolvimento,
armazenada como hash. Repetir o seed nao duplica usuarios nem redefine senhas;
reaplica os perfis e o status ativo das contas demo.

`UserFactory` oferece `admin()`, `barbeiro()`, `active()` e `inactive()`.

Referencia: [ordem de inicializacao e healthchecks do Compose](https://docs.docker.com/compose/how-tos/startup-order/).
