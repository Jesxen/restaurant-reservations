# Deploy

The app is split: the Laravel API goes on **Railway** (with MySQL), the Angular SPA
goes on **Vercel**. Deploy the backend first so you have its URL for the frontend.

## 1. Backend on Railway

1. Create a project at [railway.app](https://railway.app) and pick "Deploy from GitHub repo",
   select `Jesxen/restaurant-reservations`.
2. In the service **Settings → Root Directory**, set `backend`.
3. Add a **MySQL** database to the project (New → Database → MySQL).
4. In the backend service **Variables**, set:

   | Variable | Value |
   |----------|-------|
   | `APP_NAME` | `Restaurante La Laguna` |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_KEY` | run `php artisan key:generate --show` locally and paste it (starts with `base64:`) |
   | `APP_URL` | the backend public URL Railway gives you |
   | `FRONTEND_URL` | your Vercel URL (fill in after step 2) |
   | `DB_CONNECTION` | `mysql` |
   | `DB_HOST` | `${{MySQL.MYSQLHOST}}` |
   | `DB_PORT` | `${{MySQL.MYSQLPORT}}` |
   | `DB_DATABASE` | `${{MySQL.MYSQLDATABASE}}` |
   | `DB_USERNAME` | `${{MySQL.MYSQLUSER}}` |
   | `DB_PASSWORD` | `${{MySQL.MYSQLPASSWORD}}` |
   | `CACHE_STORE` | `database` |
   | `SESSION_DRIVER` | `file` |
   | `QUEUE_CONNECTION` | `sync` |
   | `MAIL_MAILER` | `log` |

   Optional (only if you want live deposits / SMS): `STRIPE_KEY`, `STRIPE_SECRET`,
   `STRIPE_WEBHOOK_SECRET`, `TWILIO_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM`.

5. Deploy. The start command (in `nixpacks.toml` / `Procfile`) runs
   `php artisan migrate --force` then serves on `$PORT`. To load demo data once,
   run `php artisan db:seed --force` from the Railway shell.
6. Generate a public domain (Settings → Networking → Generate Domain) and copy the URL.

> `CACHE_STORE=database` (used by rate limiting) needs the `cache` table, which
> `php artisan migrate` creates.

## 2. Frontend on Vercel

1. Set the backend URL in `frontend/src/environments/environment.prod.ts`
   (`apiUrl: 'https://YOUR-BACKEND.up.railway.app/api'`), commit and push.
2. At [vercel.com](https://vercel.com) → New Project → import the repo.
3. Set **Root Directory** to `frontend`. Vercel reads `vercel.json` for the build
   command, output directory and SPA rewrites.
4. Deploy, then copy the Vercel URL.

## 3. Wire them together

- Put the Vercel URL in the Railway `FRONTEND_URL` variable (CORS uses it) and redeploy.
- If you set up Stripe, point its webhook at `https://YOUR-BACKEND/api/stripe/webhook`.

Demo accounts: `admin@laguna.com` / `cliente@laguna.com`, password `password`.
