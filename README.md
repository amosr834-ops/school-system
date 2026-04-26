# Collaborative Task Management Web App

This project is now aligned to your semester brief and includes:
- Register/Login with JWT authentication
- Create, assign, and manage tasks
- Deadlines and priority levels
- Task comments (chat-style collaboration per task)
- Team creation and team membership
- In-app notifications
- Deployable Docker setup + CI workflow

## Stack
- Frontend: React + Vite
- Backend: PHP (JWT + REST-like endpoints)
- Database: MySQL
- Deployment: Docker Compose
- CI/CD: GitHub Actions workflow (`.github/workflows/ci.yml`)

## Run with Docker

1. Copy env file:

```powershell
Copy-Item .env.example .env
```

2. Start services:

```powershell
docker compose up --build -d
```

3. Open app:
- `http://localhost:8080`

## Environment Variables

In `.env`:
- `APP_PORT` (default `8080`)
- `MYSQL_ROOT_PASSWORD`
- `MYSQL_DATABASE` (default `collaborative_tasks`)
- `JWT_SECRET`

## API Endpoints

Base path through frontend proxy: `/api`

- `POST /api/register.php`
- `POST /api/Login.php`
- `GET /api/me.php`
- `GET|POST /api/teams.php`
- `GET|POST|PUT /api/tasks.php`
- `GET|POST /api/comments.php`
- `GET|POST /api/notifications.php`

## Notes
- Authentication implemented with JWT (satisfies "Google Auth or JWT" requirement).
- Google OAuth can be added next as an optional extension.
