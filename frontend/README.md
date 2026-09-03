# HireFlow AI — Frontend

React + Vite client for the Laravel recruitment API.

## Setup

```bash
cp .env.example .env
npm install
npm run dev
```

`VITE_API_URL` should point at the Laravel API, e.g. `http://localhost:8000/api`.

## Scripts

- `npm run dev` — local development
- `npm run build` — production build
- `npm run preview` — preview production build
- `npm run lint` — ESLint

## Architecture

- `src/api` — Axios client + endpoint modules
- `src/context` — Auth + toast providers
- `src/routes` — protected / role / guest routing
- `src/layouts` — shared AppShell-based role layouts
- `src/pages` — auth, dashboards (foundation), errors

Authentication uses Sanctum **Bearer tokens** stored via `src/utils/tokenStorage.js`.

## Security notes

- Never put OpenAI or n8n secrets in the frontend.
- All AI calls go through Laravel.
- Frontend role checks are UX only; Laravel authorizes every request.
