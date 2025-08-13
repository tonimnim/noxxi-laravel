# NOXXI Docker Setup

## Services Included

1. **PostgreSQL 16** - Main database
2. **PgAdmin** - Database management UI
3. **Redis 7** - Cache and queue backend

## Quick Start

### 1. Start Docker Services

```bash
docker-compose up -d
```

### 2. Check Services Status

```bash
docker-compose ps
```

### 3. Access Services

- **PostgreSQL**: `localhost:5432`
  - Database: `noxxi_db`
  - Username: `noxxi_user`
  - Password: `noxxi_secure_password_2024`

- **PgAdmin**: `http://localhost:5050`
  - Email: `admin@noxxi.com`
  - Password: `admin_password_2024`

- **Redis**: `localhost:6379`
  - Password: `noxxi_redis_password_2024`

## Database Management

### Connect to PostgreSQL

```bash
docker exec -it noxxi_postgres psql -U noxxi_user -d noxxi_db
```

### Run Migrations

```bash
php artisan migrate
```

### Import Existing Schema

Place your SQL files in `database/init/` directory. They will be automatically executed when the container starts for the first time.

## Stopping Services

```bash
docker-compose down
```

## Remove All Data

```bash
docker-compose down -v
```

## Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f postgres
```

## Troubleshooting

### Reset Database

```bash
docker-compose down -v
docker-compose up -d
```

### Check PostgreSQL Logs

```bash
docker logs noxxi_postgres
```

### Redis CLI

```bash
docker exec -it noxxi_redis redis-cli -a noxxi_redis_password_2024
```