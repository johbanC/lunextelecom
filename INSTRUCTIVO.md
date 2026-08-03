# Manual de instalación — Lunex Telecom

Guía paso a paso para clonar y levantar el proyecto en un computador nuevo (Windows + Laragon).

## 1. Requisitos previos

Instalar en el equipo nuevo:

- **Laragon** (incluye Apache/Nginx, MySQL, PHP): https://laragon.org/download/
- **PHP 8.3 o superior** (viene con Laragon; verificar versión con `php -v`)
- **Composer**: https://getcomposer.org/download/
- **Node.js 20+** (incluye npm): https://nodejs.org/
- **Git**: https://git-scm.com/download/win

Verificar que todo esté instalado correctamente:

```bash
php -v
composer -V
node -v
npm -v
git --version
```

## 2. Configurar Git (solo la primera vez en ese equipo)

```bash
git config --global user.name "JohbanC"
git config --global user.email "johbanc@gmail.com"
```

## 3. Clonar el repositorio

Clonar dentro de la carpeta `www` de Laragon (normalmente `C:\laragon\www`):

```bash
cd C:\laragon\www
git clone https://github.com/johbanC/lunextelecom.git
cd lunextelecom
```

## 4. Instalar dependencias de PHP

```bash
composer install
```

## 5. Instalar dependencias de JavaScript

```bash
npm install
```

## 6. Configurar el archivo de entorno (`.env`)

El archivo `.env` no viaja en el repositorio (contiene datos locales/sensibles). Crearlo a partir del ejemplo:

```bash
copy .env.example .env
```

Generar la clave de la aplicación:

```bash
php artisan key:generate
```

Editar `.env` y configurar la base de datos con **MySQL** (no dejar SQLite):

```
APP_NAME="Lunex Telecom"
APP_URL=http://lunextelecom.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lunextelecom
DB_USERNAME=root
DB_PASSWORD=
```

> Ajustar `DB_USERNAME` / `DB_PASSWORD` según la configuración de MySQL de Laragon en ese equipo.

## 7. Crear la base de datos

Con Laragon abierto (MySQL corriendo), crear la base de datos vacía. Se puede hacer desde HeidiSQL (botón derecho en Laragon → Database → HeidiSQL) o por línea de comandos:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS lunextelecom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 8. Ejecutar migraciones

```bash
php artisan migrate
```

Si se necesitan datos de prueba:

```bash
php artisan db:seed
```

## 9. Compilar los assets del frontend

Para desarrollo (con recarga en caliente):

```bash
npm run dev
```

Para producción (build final):

```bash
npm run build
```

## 10. Levantar el proyecto

Opción A — todo junto (servidor, colas, logs y Vite en paralelo):

```bash
composer run dev
```

Opción B — con Laragon: si el sitio se llama `lunextelecom` en `C:\laragon\www`, Laragon lo sirve automáticamente en `http://lunextelecom.test` (asegurarse de que Laragon esté iniciado, "Start All").

Opción C — servidor manual de Laravel:

```bash
php artisan serve
```

y abrir `http://localhost:8000`.

## 11. Flujo de trabajo con Git al día a día

Traer los últimos cambios antes de empezar a trabajar:

```bash
git pull origin main
```

Subir cambios propios:

```bash
git add .
git commit -m "Descripción del cambio"
git push origin main
```

Si se instalaron o actualizaron dependencias nuevas (`composer.json` / `package.json` cambiaron), volver a correr:

```bash
composer install
npm install
```

## Problemas comunes

- **Error de conexión a base de datos**: revisar que MySQL esté corriendo en Laragon y que `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` en `.env` coincidan con la configuración local.
- **`Vite manifest not found`**: falta correr `npm run build` (o `npm run dev` mientras se desarrolla).
- **`No application encryption key has been specified`**: falta correr `php artisan key:generate`.
- **Permisos en `storage/` o `bootstrap/cache/`**: en Windows normalmente no da problema; si aparece, dar permisos de escritura a esas carpetas.
