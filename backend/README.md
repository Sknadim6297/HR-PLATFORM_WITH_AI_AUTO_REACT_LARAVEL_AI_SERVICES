# AI HR Platform — Backend

Laravel 12 + PHP 8.2 backend for an AI-powered Recruitment & HR Automation Platform.

## Architecture

```
Job → Application → Resume (AiDocument) → AI Analysis → Job Match → Screening → HR Decision → Automation
```

AI infrastructure (unchanged):

```
Upload → Extract → Chunk → Embed → Vector Search / RAG
```

## Roles

| Role | Capabilities |
|------|--------------|
| **admin** | Full access |
| **hr** | Manage jobs, review applications, AI screening, status transitions |
| **candidate** | Profile, published jobs, own applications/documents |

## Core API Endpoints

### Auth
- `POST /api/register`
- `POST /api/login`
- `GET /api/me`
- `POST /api/logout`

### Jobs
- `GET /api/jobs` — candidates see published only; HR/Admin can filter
- `POST /api/jobs`
- `GET /api/jobs/{job}`
- `PUT /api/jobs/{job}`
- `DELETE /api/jobs/{job}`
- `POST /api/jobs/{job}/publish`
- `POST /api/jobs/{job}/close`

### Candidate profile
- `GET /api/candidate/profile`
- `PUT /api/candidate/profile`
- `GET /api/candidate/profiles/{profile}` — HR/Admin/owner

### Applications
- `POST /api/jobs/{job}/applications`
- `GET /api/jobs/{job}/applications` — HR/Admin
- `GET /api/applications`
- `GET /api/applications/{application}`
- `PATCH /api/applications/{application}/status`
- `POST /api/applications/{application}/ai-screen` — advisory only

### AI (existing)
- `POST /api/ai/documents`
- `POST /api/ai/search`
- `POST /api/ai/ask`
- `POST /api/ai/workflow`
- `POST /api/ai/generate`

### Notifications
- `GET /api/notifications`

## Application statuses

`applied → screening → shortlisted → interview → selected|rejected`

Candidate may `withdraw` from allowed stages. Invalid transitions return `422`.

## AI pipeline (queued)

1. Document extraction  
2. Chunking  
3. Embeddings  
4. `AnalyzeCandidateResume`  
5. `GenerateJobMatch`  

Only **completed** resumes are analyzed. Jobs are idempotent.

AI screening is **decision support only** — it never auto-selects/rejects.

## Automation (n8n)

Configured via `config/automation.php` / `N8N_*` env vars.

Approved workflows only (no arbitrary webhook URLs from clients). Events are recorded in `automation_events` with unique `event_key` for idempotency.

## Security model

- Sanctum authentication
- Policies for jobs, applications, profiles
- Ownership 404 semantics (no existence leaks)
- No embeddings / file paths / extracted text / prompts / API keys in API responses
- Rate limits: `api` (60/min), `ai` (20/min)
- Audit logs omit secrets and document bodies

## Environment

See `.env.example` for:

- `OPENAI_API_KEY`, `OPENAI_MODEL`
- `AI_*` embedding/RAG settings
- `N8N_ENABLED`, `N8N_BASE_URL`, workflow path mappings

## Queue

```bash
php artisan migrate
php artisan queue:work
```

`QUEUE_CONNECTION=database` recommended for local/production workers.

## Testing

```bash
php artisan test
vendor/bin/pint --test
```

External OpenAI/n8n calls are faked in tests.

## Backend freeze

This backend is feature-complete for the first React frontend release (Admin + HR + Candidate portals). Frontend is a separate milestone.
