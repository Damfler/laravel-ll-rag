# Wiki LLM RAG - Docker helpers
.PHONY: help up down build restart logs logs-app shell artisan migrate migrate-fresh composer npm-dev npm-build npm-watch key-generate pgadmin setup

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Start all containers
	docker compose up -d

up-build: ## Build and start all containers
	docker compose up -d --build

down: ## Stop all containers
	docker compose down

restart: ## Restart all containers
	docker compose restart

build: ## Build containers
	docker compose build

logs: ## Tail logs (all services)
	docker compose logs -f

logs-app: ## Tail app logs only
	docker compose logs -f app

shell: ## Open shell in app container
	docker compose exec app bash

artisan: ## Run artisan command (make artisan CMD="migrate")
	docker compose exec app php artisan $(CMD)

migrate: ## Run migrations
	docker compose exec app php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations
	docker compose exec app php artisan migrate:fresh --seed

composer: ## Run composer command (make composer CMD="require package/name")
	docker compose exec app composer $(CMD)

npm-dev: ## Build frontend assets (dev)
	docker compose exec app npm run dev

npm-build: ## Build frontend assets (production)
	docker compose exec app npm run build

npm-watch: ## Watch and rebuild frontend assets (dev)
	docker compose exec app npm run dev

key-generate: ## Generate app key
	docker compose exec app php artisan key:generate

pgadmin: ## Open pgAdmin URL in browser (shows URL)
	@echo "pgAdmin: http://localhost:$${PGADMIN_PORT:-5050}"
	@echo "Login:   $${PGADMIN_EMAIL:-admin@wiki.local} / $${PGADMIN_PASSWORD:-admin}"

setup: ## First-time setup: copy .env, build containers, migrate, seed, build assets
	cp .env.docker .env
	docker compose up -d --build
	@echo "Waiting for postgres to be healthy..."
	@until docker compose exec -T postgres pg_isready -U $${DB_USERNAME:-wiki} -d $${DB_DATABASE:-wiki} > /dev/null 2>&1; do sleep 2; done
	@echo "Waiting for redis to be healthy..."
	@until docker compose exec -T redis redis-cli ping > /dev/null 2>&1; do sleep 2; done
	@echo "Containers ready. Running setup..."
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate --seed
	docker compose exec app php artisan storage:link
	docker compose exec app npm run build
	@echo ""
	@echo "✓ Done!"
	@echo "  App:     http://localhost:$${NGINX_PORT:-8086}"
	@echo "  pgAdmin: http://localhost:$${PGADMIN_PORT:-5051}"
	@echo "  Admin:   admin@wiki.local / password"
