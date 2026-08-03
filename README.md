# Lunex Telecom

Aplicación web interna de Lunex Telecom, construida con [Laravel 13](https://laravel.com), Tailwind CSS 4 y Vite.

## Stack técnico

- PHP 8.3+ (probado con 8.4)
- Laravel Framework 13
- MySQL 8.x
- Node.js 20+ / npm
- Tailwind CSS 4 + Alpine.js
- Laragon como entorno local (Windows)

## Instalación en un equipo nuevo

Ver el manual completo paso a paso en [`INSTRUCTIVO.md`](INSTRUCTIVO.md).

Resumen rápido:

```bash
git clone https://github.com/johbanC/lunextelecom.git
cd lunextelecom
composer install
npm install
cp .env.example .env
php artisan key:generate
# configurar base de datos en .env (ver INSTRUCTIVO.md)
php artisan migrate
npm run build
php artisan serve
```

## Desarrollo diario

Levantar backend (Laravel) y frontend (Vite) en paralelo:

```bash
composer run dev
```

Esto arranca: servidor Laravel, worker de colas, logs (`pail`) y Vite, todo junto.

## Notas

- La base de datos usada es **MySQL**, no SQLite (revisar `DB_*` en `.env`).
- El archivo `.env` **no se sube a git**; cada equipo debe tener el suyo propio (ver `.env.example`).
