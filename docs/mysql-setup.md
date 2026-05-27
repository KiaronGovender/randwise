# MySQL Setup

RandWise is configured for MySQL by default in `api/.env.example`.

## 1. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS randwise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 2. Configure Laravel

In `api/.env`, set:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=randwise
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

## 3. Run migrations

```bash
cd api
php artisan migrate
```

## 4. Verify persistence

Start the API and frontend, then upload `sample-data/capitec-demo-statement.csv`.

The uploaded statement should appear in the sidebar under Saved imports and can be reopened from the database.
