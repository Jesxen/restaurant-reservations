# Restaurante La Laguna — Plataforma de reservas

Full-stack restaurant reservation platform: public landing page, client portal and admin
dashboard. Rebuilt from a legacy PHP/Blade demo into a modern, decoupled architecture.

| Layer    | Stack                                                                 |
|----------|-----------------------------------------------------------------------|
| Backend  | Laravel 13 · REST API · Sanctum (token auth) · MySQL 8                 |
| Frontend | Angular 21 · standalone · signals · Tailwind v4 · DaisyUI 5 · Leaflet  |

## Features

- **Landing page** — hero, dynamic menu from the API, gallery, location map (Leaflet).
- **Auth + roles** — register/login (Sanctum Bearer tokens); `client` and `admin` roles.
- **Reservations** — guest or authenticated; lifecycle `pendiente → confirmada → completada / cancelada / no_show`.
- **Availability** — anti-overbooking: seats are validated against active table capacity per date/time slot.
- **Client portal** (`/cuenta`) — view own reservations, cancel, profile.
- **Admin panel** (`/admin`) — KPI dashboard, reservation management (filter, change status, assign table), table CRUD, menu CRUD.

## Architecture

```
restaurant-reservations/
├── backend/    Laravel 13 API  (http://localhost:8000)
└── frontend/   Angular 21 SPA  (http://localhost:4200)
```

The Angular SPA calls the Laravel API over JSON with a Bearer token (added by an HTTP
interceptor). CORS is restricted to the frontend origin (`FRONTEND_URL` in `backend/.env`).

### Demo accounts (seeded)

| Role   | Email               | Password   |
|--------|---------------------|------------|
| Admin  | `admin@laguna.com`  | `password` |
| Client | `cliente@laguna.com`| `password` |

### API overview

Public: `POST /api/register`, `POST /api/login`, `GET /api/menu`,
`GET /api/disponibilidad?fecha&hora`, `POST /api/reservas`.

Authenticated (`Bearer`): `GET /api/me`, `POST /api/logout`, `GET /api/mis-reservas`,
`PATCH /api/reservas/{id}/cancelar`.

Admin (`Bearer` + admin role): `GET /api/admin/dashboard`, `GET/PATCH /api/admin/reservas`,
`apiResource /api/admin/{mesas,categorias,platos}`.

## Setup

### Database
```sql
CREATE DATABASE restaurante CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Backend
```bash
cd backend
composer install
cp .env.example .env        # set DB_* and FRONTEND_URL
php artisan key:generate
php artisan migrate --seed
php artisan serve            # http://localhost:8000
```

### Frontend
```bash
cd frontend
npm install --legacy-peer-deps
npm start                    # http://localhost:4200
```

> If npm fails with `UNABLE_TO_VERIFY_LEAF_SIGNATURE`, your network is intercepting TLS
> (antivirus/proxy). Point Node at the proxy root CA:
> `setx NODE_EXTRA_CA_CERTS "C:\path\to\root-ca.pem"`.

## Production build (frontend)
```bash
cd frontend
npm run build                # outputs to dist/frontend/browser
```

© Restaurante La Laguna — Jesxen
