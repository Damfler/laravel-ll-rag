# DEVLOG — Wiki LLM RAG

Журнал разработки. Каждая запись — сессия работы или завершённый этап.
Самые свежие записи — **сверху**.

---

## 2026-04-07 — Группы доступа + Фаза 3 (импорт DokuWiki)

**Группы доступа:**
- Архитектура: `groups` + `group_user` (пользователи) + `space_group` (разделы)
- `is_public` (boolean) заменён на `visibility` (public / restricted / private) на таблице `spaces`
- `accessibleBy()` в `Space` теперь проверяет пересечение групп пользователя и групп раздела
- Добавлен `Admin/GroupController` с управлением участниками
- Vue: страницы групп в admin-панели, форма разделов переработана под 3 варианта доступа
- Логика: admin видит всё, владелец раздела — всегда, restricted — только своя группа

**Фаза 3 — импорт DokuWiki:**
- `DokuWikiParser` — конвертер DokuWiki-разметки в TipTap JSON
  - Поддерживает: заголовки 1-5, bold, italic, strike, code, ссылки, изображения, таблицы, списки, `<code>`, `<WRAP>` → blockquote
- `artisan wiki:import-dokuwiki` — команда импорта
  - Маппинг: папки `old_wiki/pages/` → spaces, файлы → pages с иерархией по вложенности
  - `--dry-run` — preview без записи в БД
  - Копирует медиафайлы в `storage/app/public/wiki-media/`
- Запуск: `make artisan CMD="wiki:import-dokuwiki --dry-run"` для проверки

**Следующий шаг:**
Запустить `--dry-run`, проверить маппинг, затем боевой запуск → Фаза 4 (RAG).

---

## 2026-04-07 — Аудит Фазы 1–2 + исправления + панель администратора

**Аудит выявил:**
- Критично: отсутствовали Policy-классы → `$this->authorize()` в контроллерах падало с 403
- Критично: отсутствовал `Spaces/Edit.vue` → редактирование раздела было недоступно
- Spaces в сайдбаре не передавались → sidebar был пустой
- Flash-сообщения не пробрасывались через Inertia shared props
- `make setup` не собирал фронтенд и не создавал storage symlink

**Исправлено:**
- `app/Policies/SpacePolicy.php` — методы `update`, `delete`, `editor` (для PageController)
- `app/Http/Middleware/HandleInertiaRequests.php` — добавлена ленивая загрузка `spaces` и `flash` в shared props
- `resources/js/Layouts/AppLayout.vue` — убран prop `spaces`, теперь читается из `usePage().props`; добавлен flash-error; добавлена ссылка «Пользователи» в sidebar для admin
- `resources/js/Pages/Spaces/Edit.vue` — создан (форма редактирования + опасная зона удаления)
- `Makefile` → `setup` теперь выполняет: `migrate --seed` + `storage:link` + `npm run build`; добавлен `make npm-watch`

**Добавлено — Панель администратора:**
- `app/Http/Controllers/Admin/UserController.php` — CRUD пользователей (index, create, store, edit, update, destroy)
- `resources/js/Pages/Admin/Users/Index.vue` — таблица пользователей с ролями и пагинацией
- `resources/js/Pages/Admin/Users/Create.vue` — форма создания пользователя
- `resources/js/Pages/Admin/Users/Edit.vue` — форма редактирования + сброс пароля + удаление
- `routes/web.php` — группа `/admin` с middleware `role:admin`

**Следующий шаг:**
Фаза 3 — импорт DokuWiki (`DokuWikiParser` + artisan-команда).

---

## 2026-04-07 — Планирование и инициализация проекта

**Контекст:**
Проект — корпоративная wiki (МТК Росберг) с LLM + RAG поиском.
Цель: заменить DokuWiki на современное решение на Laravel + Vue, добавить AI-ассистента.

**Принятые решения:**

| Вопрос | Решение | Причина |
|--------|---------|---------|
| LLM/RAG провайдер | **OpenRouter** | OpenAI-совместимый API, 200+ моделей, бесплатные модели для разработки, один ключ |
| Embeddings | OpenAI `text-embedding-3-small` | Дёшево (~$0.02/1M токенов), 1536 dim, качественно |
| Локальная альтернатива | Ollama (опционально) | Для dev без интернета |
| Формат контента | TipTap JSON (уже реализован) | Богатый UX редактора, `content_text` для RAG |
| Миграция DokuWiki | PHP конвертер `DokuWikiParser` | ~333 страниц в синтаксисе DokuWiki |

**Текущее состояние (из кода):**
- Фазы 1–2 в основном завершены: auth, roles, spaces, pages, TipTap editor, версионирование, теги, поиск
- Реализованы компоненты: `Editor/`, `Sidebar/`, `Wiki/*.vue`, `Spaces/*.vue`
- Docker: app (PHP-FPM) + nginx + postgres (pgvector) + redis + queue + pgadmin
- Тесты: phpunit, sqlite in-memory для тестов

**Создано:**
- `TASKS.md` — список задач по всем фазам
- `DEVLOG.md` — этот файл
- `CLAUDE.md` — инструкции для AI-ассистента

**Следующий шаг:**
Завершить Фазу 2 (автосохранение, diff версий, откат) → Фаза 3 (импорт DokuWiki).

---

## 2026-03-06 — Старт проекта (по коммитам)

**Сделано:**
- Установка Laravel 12 + Breeze (Inertia + Vue)
- Настройка Docker Compose (postgres/pgvector, redis, nginx, queue, pgadmin)
- Миграции: users, roles, role_user, spaces, pages, tags, page_versions, page_tag
- Middleware `EnsureHasRole`, трейт `HasRoles`, сидер `RoleSeeder`
- Базовый layout (sidebar с деревом страниц, header, breadcrumbs)
- Контроллеры: SpaceController, PageController, SearchController
- TipTap редактор с расширениями (tables, code blocks, task lists, links, images)
- История версий: Observer на модели Page, страница `Wiki/History.vue`
- Полнотекстовый поиск (ilike)
- Настройка Vite 7, ESLint, Prettier, Pint (Laravel preset)
