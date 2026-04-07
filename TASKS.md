# TASKS — Wiki LLM RAG

Файл задач проекта. Статусы: `[ ]` — не начато, `[~]` — в работе, `[x]` — готово, `[!]` — заблокировано / требует решения.

---

## Фаза 1 — Фундамент ✅ ГОТОВО

- [x] Laravel 12 + Breeze + Inertia + Vue 3
- [x] PostgreSQL + Redis (docker-compose)
- [x] Роли: admin / editor / viewer, middleware `role:`, трейт `HasRoles`
- [x] Базовый layout (sidebar, header, breadcrumbs)
- [x] Vite 7 + TailwindCSS + ESLint + Pint
- [x] SpacePolicy + PagePolicy (авторизация create/update/delete)
- [x] Shared props: spaces + flash через HandleInertiaRequests
- [x] Docker setup: `make setup` запускает migrate --seed + npm build + storage:link
- [x] Панель администратора: управление пользователями (CRUD)

---

## Группы доступа ✅ ГОТОВО

- [x] Миграции: `groups`, `group_user`, `space_group`, `visibility` на spaces (вместо `is_public`)
- [x] `Group` модель; `groups()` в `User` через `HasRoles`; обновлён `accessibleBy()` в `Space`
- [x] `Admin/GroupController` — CRUD групп + управление участниками
- [x] Vue: `Admin/Groups/Index`, `Create`, `Edit` (чекбокс-список участников с поиском)
- [x] `Spaces/Create` и `Spaces/Edit` — выбор visibility (public/restricted/private) + multiselect групп
- [x] Sidebar AppLayout: ссылка «Группы доступа» для admin
- [x] Роуты: `/admin/groups` с middleware `role:admin`

---

## Фаза 2 — Ядро Wiki ✅ ГОТОВО

- [x] Spaces CRUD (разделы вики)
- [x] Spaces/Edit.vue — страница редактирования раздела
- [x] Pages CRUD (иерархия, soft delete, slug)
- [x] TipTap editor (H1-H3, списки, таблицы, код, task list, цитаты)
- [x] Версионирование страниц (`page_versions` через booted-хук)
- [x] Теги (many-to-many `page_tag`)
- [x] Полнотекстовый поиск (ilike по title + content_text)
- [x] История версий (Wiki/History.vue)
- [x] Автосохранение черновика в localStorage
- [x] Diff между версиями (показ изменений)
- [x] Откат к предыдущей версии
- [x] Фильтрация страниц по тегам

---

## Фаза 3 — Импорт DokuWiki 📦 ГОТОВО

> Источник: `old_wiki/` — ~333 .txt файла в синтаксисе DokuWiki, + медиафайлы

- [x] `app/Services/Wiki/DokuWikiParser.php` — конвертер DokuWiki → TipTap JSON
  - Заголовки всех уровней, **жирный**, //курсив//, ~~зачёркнутый~~, ''код''
  - Ссылки `[[url|title]]`, изображения `{{file.jpg}}`, таблицы `| cell |`
  - Списки `  *` и `  -`, блоки `<code>`, `<WRAP>` → blockquote, `----`
- [x] `app/Console/Commands/ImportDokuWiki.php` — artisan `wiki:import-dokuwiki`
  - Маппинг: топ-уровень папок → spaces, файлы + вложенность → pages с parent_id
  - Флаги: `--dry-run`, `--path=`, `--media=`, `--user=`, `--skip-wiki`
  - Показывает таблицу-план, копирует медиафайлы, выводит итоговую статистику
- [ ] Тест-прогон: `make artisan CMD="wiki:import-dokuwiki --dry-run"` — проверить маппинг

---

## Фаза 4 — RAG Pipeline 🔍 НЕ НАЧАТО

- [ ] Миграция: `page_chunks` (id, page_id, content, embedding vector(1536), chunk_index, metadata jsonb)
- [ ] Индекс HNSW в pgvector для быстрого поиска
- [ ] `PageChunker` сервис: HTML → plain text → чанки (~500 токенов, перекрытие 50)
- [ ] Интерфейс `EmbeddingProviderInterface`
- [ ] Драйвер `OpenAiEmbeddingProvider` (text-embedding-3-small через OpenAI API)
- [ ] Драйвер `OllamaEmbeddingProvider` (для локальной разработки)
- [ ] Job `IndexPageJob`: чанкинг + эмбеддинг + сохранение
- [ ] Job `DeletePageIndexJob`: удаление чанков при soft-delete страницы
- [ ] Event Listener: автозапуск индексации при сохранении/обновлении страницы
- [ ] Команда `artisan wiki:reindex [--space=slug]`
- [ ] `HybridSearchService`: FTS + векторный поиск + Reciprocal Rank Fusion

---

## Фаза 5 — LLM Интеграция 🤖 НЕ НАЧАТО

> LLM провайдер: **OpenRouter** (openai-compatible API, 200+ моделей)
> Конфиг: `LLM_PROVIDER=openrouter`, `OPENROUTER_API_KEY=...`

- [ ] `config/llm.php` — конфиг провайдеров
- [ ] Интерфейс `LlmProviderInterface` (методы `chat()`, `chatStream()`)
- [ ] Драйвер `OpenRouterProvider` (совместим с OpenAI SDK, меняем только base_url)
- [ ] Драйвер `OllamaProvider` (для локальной разработки)
- [ ] `RagService`: вопрос → HybridSearch → формирование контекста → LLM prompt → ответ
- [ ] Системный промпт: отвечать только по контексту вики, цитировать источники
- [ ] API роут `POST /api/chat`
- [ ] Server-Sent Events (SSE) для стримингового вывода
- [ ] Vue компонент `AiChat` с анимацией печати
- [ ] Миграции: `conversations`, `messages` (история диалогов)
- [ ] UI: боковая панель с историей чатов

---

## Фаза 6 — Расширенные возможности 🚀 НЕ НАЧАТО

- [ ] Кнопка «Краткое саммари страницы» (LLM one-shot)
- [ ] «Похожие страницы» на основе векторного сходства
- [ ] AI-поиск прямо в строке поиска (search + RAG inline)
- [ ] Авто-предложение тегов при сохранении страницы
- [ ] Экспорт страницы в Markdown / PDF
- [ ] Счётчик просмотров, топ страниц
- [ ] Тёмная тема
- [ ] Уведомления об изменениях страниц (email / in-app)

---

## Технический долг / Баги

- [ ] Добавить feature-тесты для SpaceController, PageController, Admin/UserController
- [ ] Автосохранение черновика в localStorage (Wiki/Create.vue + Wiki/Edit.vue)
