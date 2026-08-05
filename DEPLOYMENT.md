# Production Deployment Guide: Render & Railway

This document details how to deploy this Laravel-based AI Form Builder application to **Railway** or **Render**.

---

## 1. Prerequisites & Environment Configuration
Ensure you have the following before deploying:
- A GitHub repository containing the application code.
- A database service (PostgreSQL or MySQL).
- Laravel environment configuration variables set up.

---

## 2. Deploying to Railway (Recommended)

Railway is highly recommended because it supports PostgreSQL/MySQL databases, environment injection, and background workers natively.

### Step A: Provision a Database on Railway
1. Log in to your [Railway Dashboard](https://railway.app/).
2. Click **New Project** -> **Provision PostgreSQL** (or MySQL).
3. Railway automatically sets up the credentials and exposes database connection variables.

### Step B: Deploy the Application
1. Click **New Project** -> **Deploy from GitHub repo**.
2. Select your repository.
3. In the service settings, add the following variables under the **Variables** tab:
   - `APP_NAME`: `AI Form Builder`
   - `APP_ENV`: `production`
   - `APP_DEBUG`: `false`
   - `APP_KEY`: *(Generate locally via `php artisan key:generate` and paste the string)*
   - `APP_URL`: `https://${RAILWAY_STATIC_URL}` *(Railway automatically resolves this dynamic domain)*
   - `DB_CONNECTION`: `pgsql` (or `mysql`)
   - `DB_HOST`: `${{PGHOST}}`
   - `DB_PORT`: `${{PGPORT}}`
   - `DB_DATABASE`: `${{PGDATABASE}}`
   - `DB_USERNAME`: `${{PGUSER}}`
   - `DB_PASSWORD`: `${{PGPASSWORD}}`
   - `QUEUE_CONNECTION`: `database` *(Required for the AI background processes)*

### Step C: Configure Custom Build & Start Commands
1. In the service settings, set the **Build Command** to:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
2. Set the **Start Command** to run database migrations first and start the PHP/Apache server:
   ```bash
   php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && apache2-foreground
   ```
   *(If deploying via a Dockerfile, make sure the Apache document root points to `/app/public`)*

### Step D: Set up the Queue Worker (Background Service)
Since AI generation and AI edits run asynchronously:
1. In the same project, add a new service pointing to the **same** GitHub repository.
2. Under service settings, name it `queue-worker`.
3. Set the **Start Command** to:
   ```bash
   php artisan queue:work --verbose --tries=3 --timeout=90
   ```
4. Copy the same environment variables from the main web service (especially database variables).

---

## 3. Deploying to Render

Render requires a custom setup for file storage (using persistent disks or AWS S3) due to its ephemeral filesystem.

### Step A: Provision a PostgreSQL Database
1. In the [Render Dashboard](https://dashboard.render.com/), click **New** -> **PostgreSQL**.
2. Name the database, select a region, and click **Create Database**.
3. Copy the **Internal Database URL** for Render services.

### Step B: Create a Web Service
1. Click **New** -> **Web Service**.
2. Connect your GitHub repository.
3. Configure the following service settings:
   - **Runtime**: `PHP` (or `Docker` if utilizing the custom PHP Dockerfile)
   - **Build Command**:
     ```bash
     composer install --no-dev --optimize-autoloader
     ```
   - **Start Command**:
     ```bash
     php artisan migrate --force && php artisan config:cache && apache2-foreground
     ```
4. Add the following Environment Variables in the **Environment** tab:
   - `APP_NAME`: `AI Form Builder`
   - `APP_ENV`: `production`
   - `APP_DEBUG`: `false`
   - `APP_KEY`: *(Your generated Laravel key)*
   - `DB_CONNECTION`: `pgsql`
   - `DATABASE_URL`: *(Paste the PostgreSQL Internal Database URL copied in Step A)*
   - `QUEUE_CONNECTION`: `database`
   
### Step C: Setup a Queue Worker on Render
1. Click **New** -> **Background Worker**.
2. Connect the same repository.
3. Set the **Start Command** to:
   ```bash
   php artisan queue:work --tries=3
   ```
4. Link the same environment variables database configurations.

---

## 4. Notes on Ephemeral Filesystems & File Uploads
Both Render and Railway containers are ephemeral. Any files uploaded to `storage/app/public/` will be lost when a new build is deployed or a container restarts.
- **Solution 1 (Railway Disk)**: Create and mount a **Volume** at `/app/storage/app/public` in Railway.
- **Solution 2 (Render Disk)**: Mount a **Persistent Disk** at `/opt/render/project/src/storage/app/public` (adjusting your path to match the Laravel storage path).
- **Solution 3 (S3 / Cloud Storage)**: Update `FILESYSTEM_DISK` to `s3` in your environment variables, and configure AWS/DigitalOcean Spaces credentials.
