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
Nenhuma biblioteca de UI ou migration de negocio foi adicionada.

Referencia: [ordem de inicializacao e healthchecks do Compose](https://docs.docker.com/compose/how-tos/startup-order/).
