<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## BMC SmartFind Docker

This project is a Laravel 12 web application for searching and managing Quality documents. It uses MariaDB for application data and has an optional local FastAPI service for semantic search.

The original delivery contains three folders. They can be delivered as one package with this structure:

```text
BMC-SmartFind-Deploy/
  docker-compose.yml
  app/       # contents of BMC_Project/BMC-SmartFind
  data/      # contents of BMC_Data/SmartFindDocuments
  model/     # contents of BMC_AI/Models/multilingual-e5-small
```

The folders do not need to be merged into one directory. They are mounted into the containers as `/mnt/documents` and `/models/multilingual-e5-small`. This keeps the Laravel source, uploaded documents, and AI model separate and prevents a rebuild from deleting data. For the current workstation layout, the default paths are `../../BMC_Data/SmartFindDocuments` and `../../BMC_AI/Models/multilingual-e5-small`. For the single-package layout, set `DOCUMENTS_PATH=./data` and `AI_MODEL_PATH=./model` in `.env`.

For GitHub and Portainer, use the contents of `BMC-SmartFind` as the repository root. Do not put it inside an additional `app` directory unless the Docker build context is changed as well. Keep `data` and `model` outside the Git repository, or mount them from absolute server paths.

### Run locally with Docker Desktop

```powershell
Copy-Item .env.example .env
docker compose up -d --build
```

Open `http://localhost:8080`. The first startup creates the application key and runs migrations. Database, uploaded documents, and logs are stored in Docker volumes.

To restore an existing SQL backup after the stack is running, import it into the database container and then copy the matching document backup into the `app_storage` volume. For example, from PowerShell:

```powershell
Get-Content .\storage\app\smartfind-backups\<backup-id>\database\bmc_smartfind.sql | docker compose exec -T db mariadb -u bmc -pbmc_local_password bmc_smartfind
```

Do not commit real `.env` files or production database passwords. Change the example passwords before exposing the stack beyond localhost.

The AI service is disabled by default because its offline embedding model is not included in this repository. The provided `.env.example` expects the sibling folder `../BMC_AI/Models/multilingual-e5-small`. Set `AI_SMART_SEARCH_ENABLED=true`, and start the AI profile:

```powershell
docker compose --profile ai up -d --build
```

### Deploy in Portainer

Create a Stack from the Git repository and use `docker-compose.yml` as the Compose path. Set `APP_PORT`, database passwords, `APP_URL`, `DOCUMENTS_PATH`, and `AI_MODEL_PATH` in the Stack environment variables. Use absolute server paths such as `/opt/bmc-smartfind/data/SmartFindDocuments` and `/opt/bmc-smartfind/model/multilingual-e5-small`. For AI, enable the `ai` profile or remove the `profiles` line from the AI service.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
