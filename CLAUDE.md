# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Files

- `TASKS.md` — все задачи по фазам, текущий статус
- `DEVLOG.md` — журнал разработки, принятые решения, история изменений

## Project Overview

A company wiki application (МТК Росберг) with LLM + RAG capabilities. Built with Laravel 12 (PHP 8.4), Vue 3 + Inertia.js, PostgreSQL 16 + pgvector, and Redis. The project follows a phased roadmap: Phases 1–2 (wiki core) are complete; Phases 3–4 (RAG pipeline + LLM integration) are in progress.

## Development Commands

All commands run inside Docker containers. Use `make` targets or the `artisan`/`composer` wrappers.

```bash
make setup          # First-time setup: copy .env.docker, build containers, run migrations
make up             # Start all containers
make down           # Stop all containers
make shell          # Open bash in app container
make migrate        # Run migrations
make migrate-fresh  # Drop all tables, re-run migrations + seeders
make artisan CMD="<cmd>"   # Run any artisan command
make composer CMD="<cmd>"  # Run any composer command
make npm-dev        # Build frontend assets (dev)
make npm-build      # Build frontend assets (production)
```

**Running tests:**
```bash
make artisan CMD="test"                          # All tests
make artisan CMD="test --filter TestClassName"   # Single test class
make artisan CMD="test tests/Feature/SomeTest.php"  # Single file
```

**Local development (without Docker):**
```bash
composer run dev    # Starts PHP server, queue worker, pail logger, and Vite concurrently
composer run test   # Clears config cache and runs tests
```

**Code style:**
```bash
make artisan CMD="pint"       # Fix PHP code style (Laravel preset, single quotes)
npm run lint                  # Lint Vue/JS files
npm run lint:fix              # Auto-fix Vue/JS linting
npm run format                # Prettier format
```

## Architecture

### Backend (`app/`)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── SpaceController.php   # CRUD for wiki spaces
│   │   ├── PageController.php    # CRUD for wiki pages (with versioning)
│   │   └── SearchController.php  # Full-text search (ilike on title + content_text)
│   └── Middleware/
│       └── EnsureHasRole.php     # Role-based access, aliased as `role:` in routes
├── Models/
│   ├── User.php        # Uses HasRoles trait
│   ├── Space.php       # Wiki spaces (sections)
│   ├── Page.php        # Wiki pages with parent/child hierarchy, soft delete
│   ├── PageVersion.php # Auto-saved via Observer on Page model
│   ├── Role.php        # admin / editor / viewer
│   └── Tag.php         # Many-to-many with Page
└── Services/           # Planned: Llm/, Rag/, Wiki/ subdirectories
```

**Planned service structure (Phases 3–4):**
- `app/Services/Llm/` — `LlmProviderInterface`, `OpenAiProvider`, `AnthropicProvider`, `OllamaProvider`
- `app/Services/Rag/` — `PageChunker`, `EmbeddingProviderInterface`, `HybridSearchService`, `RagService`
- `app/Jobs/` — `IndexPageJob`, `DeletePageIndexJob`

### Frontend (`resources/js/`)

```
resources/js/
├── Pages/
│   ├── Spaces/         # Index.vue, Create.vue, Show.vue
│   ├── Wiki/           # Create.vue, Edit.vue, Show.vue, History.vue
│   ├── Search/         # Results page with highlighting
│   └── Dashboard.vue
├── Components/
│   ├── Editor/         # TipTap rich text editor integration
│   ├── Sidebar/        # Navigation with page hierarchy tree
│   └── UI/             # Reusable UI components
└── Layouts/
```

### Database

- `spaces` — wiki sections (name, slug, owner_id, icon, color, is_public)
- `pages` — wiki pages (space_id, parent_id for hierarchy, content as TipTap JSON, soft delete)
- `page_versions` — auto-saved on each page update via Observer
- `roles` + `role_user` — RBAC (admin/editor/viewer)
- `tags` + `page_tag` — tagging system
- **Planned:** `page_chunks` with `vector(1536)` column for pgvector embeddings (requires `pgvector` extension, already enabled via `docker/postgres/init.sql`)

### Infrastructure (Docker)

Services: `app` (PHP-FPM), `nginx` (port 8084), `postgres` (pgvector/pgvector:pg16), `redis`, `queue` (worker), `pgadmin` (port 5050).

Ollama service is defined but commented out in `docker-compose.yml`.

## Key Environment Variables

```
LLM_PROVIDER=openai          # openai | anthropic | ollama
OPENAI_API_KEY=...
ANTHROPIC_API_KEY=...
OLLAMA_BASE_URL=http://ollama:11434
```

## Conventions

- PHP: Laravel preset via Pint — single quotes, alpha-sorted imports, trailing commas in multiline
- Tests use SQLite in-memory; test env is configured in `phpunit.xml`
- Role middleware alias: `role:admin` in route definitions
- Page content is stored as TipTap JSON; plain text for search is stored separately in `content_text`
- Queue connection is Redis; use `sync` in tests (already configured in `phpunit.xml`)
