# Restaurante La Laguna · Plataforma de reservas

Full-stack restaurant reservation platform with a public landing page, a client portal and an
admin dashboard. It started as a legacy PHP/Blade demo and was rebuilt into a decoupled API + SPA.

| Layer    | Stack                                                                 |
|----------|-----------------------------------------------------------------------|
| Backend  | Laravel 13 · REST API · Sanctum (token auth) · MySQL 8                 |
| Frontend | Angular 21 · standalone · signals · Tailwind v4 · DaisyUI 5 · Leaflet  |

## Features

- **Landing page.** Hero, dynamic menu, gallery and a Leaflet location map, all fed from editable settings.
- **Auth and roles.** Register and login with Sanctum bearer tokens, `client`/`staff`/`admin` roles, password recovery (forgot/reset) and email verification.
- **Reservations.** Guest or authenticated, with the lifecycle `pendiente → confirmada → completada / cancelada / no_show` and a unique booking reference (*localizador*).
- **Discrete time slots.** Bookable slots are generated from the configured service windows and interval, so the client picks a slot instead of typing a free time.
- **Availability.** Overbooking is blocked per turno: a booking occupies `[hora, hora+duración)` and seats are checked against every overlapping reservation in that window.
- **Booking rules.** Service hours, a booking window (from today up to N days), minimum lead time, max online party size, weekly closed days and one-off blackout dates.
- **Client portal** (`/cuenta`). View, edit (date, slot, party size, notes) and cancel your own reservations. Editing a confirmed booking sends it back to pending for re-confirmation.
- **Emails.** Confirmation and status-change notices to the customer, a new-booking alert to the restaurant, and a scheduled day-before reminder command.
- **Admin panel** (`/admin`). KPI dashboard, reservation management (filter, search, change status, assign table), CRUD for tables, menu and users, a blackout-date manager, review moderation, a waitlist view and a customizable settings panel (branding and colors, contact, social, map coordinates, slot config, gallery, closed days and deposit).
- **Reviews.** Clients with a completed reservation can leave a rating and comment. Reviews are moderated by staff before they show on the landing page, together with the average score.
- **Waitlist.** When a slot is full, guests can join the waitlist and get notified (email, and SMS if a phone is given) as soon as a matching table frees up.
- **Deposits (Stripe).** Optional per-person deposit taken at booking through Stripe (Payment Element plus webhook). It stays disabled until Stripe is configured.
- **SMS (Twilio).** Confirmation, reminder and waitlist notices by SMS when a phone is provided, skipped silently otherwise.
- **Multi-language.** Runtime ES/EN switch across the public site, booking and auth flows.
- **Security.** Per-route rate limiting, security headers, a strong password policy, strict mass-assignment whitelisting, a locked CORS origin and policy-based authorization. The backend ships with a 50-test PHPUnit feature suite.

## Screenshots

| Home | Reservar | Contacto |
|------|----------|----------|
| ![Home](docs/screenshots/home.png) | ![Reservar](docs/screenshots/reservar.png) | ![Contacto](docs/screenshots/contacto.png) |

| Mi cuenta | Admin · Dashboard | Admin · Reservas |
|-----------|-------------------|------------------|
| ![Cuenta](docs/screenshots/cuenta.png) | ![Dashboard](docs/screenshots/admin-dashboard.png) | ![Reservas](docs/screenshots/admin-reservas.png) |

| Admin · Ajustes | Admin · Reseñas | Admin · Lista de espera |
|-----------------|-----------------|-------------------------|
| ![Ajustes](docs/screenshots/admin-ajustes.png) | ![Reseñas](docs/screenshots/admin-reviews.png) | ![Waitlist](docs/screenshots/admin-waitlist.png) |

Screenshots are generated with Playwright against the running app:
`node frontend/tools/screenshots.mjs` writes them to `docs/screenshots/`.

## Architecture

```
restaurant-reservations/
├── backend/    Laravel 13 API  (http://localhost:8000)
└── frontend/   Angular 21 SPA  (http://localhost:4200)
```

The Angular SPA talks to the Laravel API over JSON with a bearer token added by an HTTP
interceptor. CORS is restricted to the frontend origin (`FRONTEND_URL` in `backend/.env`).

### Demo accounts (seeded)

| Role   | Email               | Password   |
|--------|---------------------|------------|
| Admin  | `admin@laguna.com`  | `password` |
| Client | `cliente@laguna.com`| `password` |

### API overview

Public: `POST /api/register`, `POST /api/login`, `POST /api/forgot-password`,
`POST /api/reset-password`, `GET /api/menu`, `GET /api/settings`,
`GET /api/disponibilidad?fecha&hora`, `GET /api/horarios?fecha`, `POST /api/reservas`,
`POST /api/contacto`, `GET /api/reviews`, `POST /api/waitlist`, `POST /api/stripe/webhook`.

Authenticated (`Bearer`): `GET /api/me`, `POST /api/logout`,
`POST /api/email/verification-notification`, `GET /api/mis-reservas`,
`PATCH /api/reservas/{id}` (edit), `PATCH /api/reservas/{id}/cancelar`,
`POST /api/reviews`, `GET /api/mis-esperas`.

Admin (`Bearer` + staff/admin): `GET /api/admin/dashboard`,
`GET/PATCH /api/admin/reservas`, `GET /api/admin/reservas/export`,
`apiResource /api/admin/{mesas,categorias,platos,usuarios,blackout-dates}`,
`GET/PATCH /api/admin/settings`, `GET/PATCH/DELETE /api/admin/reviews`,
`GET/DELETE /api/admin/waitlist`.

Reservation reminders run with `php artisan reservas:recordatorios` (scheduled daily).

Optional integrations, set in `backend/.env`: Stripe deposits
(`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`) and Twilio SMS
(`TWILIO_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM`). Both stay off and degrade cleanly when unset.

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

If npm fails with `UNABLE_TO_VERIFY_LEAF_SIGNATURE`, your network is intercepting TLS
(antivirus or proxy). Point Node at the proxy root CA:
`setx NODE_EXTRA_CA_CERTS "C:\path\to\root-ca.pem"`.

## Production build (frontend)
```bash
cd frontend
npm run build                # outputs to dist/frontend/browser
```

© Restaurante La Laguna · Jesxen
