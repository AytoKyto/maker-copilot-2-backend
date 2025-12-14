# Maker Copilot Backend - Makefile
#
# Simplified command interface for Symfony backend development.
# All commands use English per CLAUDE.md requirements.
#
# @see https://symfony.com/doc/current/setup.html

.DEFAULT_GOAL := help
.PHONY: help

# Colors for output
COLOR_RESET   = \033[0m
COLOR_INFO    = \033[32m
COLOR_COMMENT = \033[33m
COLOR_ERROR   = \033[31m
COLOR_TITLE   = \033[34m

## —— 🎯 Maker Copilot Backend Makefile ————————————————————————————————————————
help: ## Show this help message
	@echo "${COLOR_TITLE}Maker Copilot Backend - Available commands:${COLOR_RESET}"
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "${COLOR_INFO}%-30s${COLOR_RESET} %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— 🚀 Setup & Installation ————————————————————————————————————————————————————
install: ## Install Composer dependencies
	@echo "${COLOR_INFO}Installing Composer dependencies...${COLOR_RESET}"
	composer install
	@echo "${COLOR_INFO}Done!${COLOR_RESET}"

setup: install ## Complete project setup (install + database + JWT)
	@echo "${COLOR_INFO}Setting up project...${COLOR_RESET}"
	@$(MAKE) db-create
	@$(MAKE) db-migrate
	@$(MAKE) jwt-generate
	@echo "${COLOR_INFO}Setup complete! Run 'make dev' to start the server.${COLOR_RESET}"

update: ## Update Composer dependencies
	@echo "${COLOR_INFO}Updating dependencies...${COLOR_RESET}"
	composer update
	@echo "${COLOR_INFO}Done!${COLOR_RESET}"

check-requirements: ## Check if required tools are installed
	@echo "${COLOR_INFO}Checking requirements...${COLOR_RESET}"
	@command -v php >/dev/null 2>&1 || { echo "${COLOR_ERROR}PHP is not installed${COLOR_RESET}"; exit 1; }
	@command -v composer >/dev/null 2>&1 || { echo "${COLOR_ERROR}Composer is not installed${COLOR_RESET}"; exit 1; }
	@command -v symfony >/dev/null 2>&1 || { echo "${COLOR_ERROR}Symfony CLI is not installed${COLOR_RESET}"; exit 1; }
	@echo "${COLOR_INFO}All requirements satisfied!${COLOR_RESET}"

jwt-generate: ## Generate JWT keys
	@echo "${COLOR_INFO}Generating JWT keys...${COLOR_RESET}"
	php bin/console lexik:jwt:generate-keypair --skip-if-exists
	@echo "${COLOR_INFO}JWT keys generated!${COLOR_RESET}"

## —— 🔧 Development ——————————————————————————————————————————————————————————————
dev: ## Start Symfony development server
	@echo "${COLOR_INFO}Starting Symfony development server...${COLOR_RESET}"
	symfony server:start -d
	@echo "${COLOR_INFO}Server started at http://127.0.0.1:8001${COLOR_RESET}"

stop: ## Stop Symfony development server
	@echo "${COLOR_INFO}Stopping Symfony server...${COLOR_RESET}"
	symfony server:stop
	@echo "${COLOR_INFO}Server stopped!${COLOR_RESET}"

logs: ## Show server logs
	symfony server:log

routes: ## List all application routes
	php bin/console debug:router

debug-container: ## Debug service container
	php bin/console debug:container

debug-env: ## Show current environment variables
	php bin/console debug:dotenv

## —— 🗄️  Database ——————————————————————————————————————————————————————————————————
db-create: ## Create database
	@echo "${COLOR_INFO}Creating database...${COLOR_RESET}"
	php bin/console doctrine:database:create --if-not-exists
	@echo "${COLOR_INFO}Database created!${COLOR_RESET}"

db-drop: ## Drop database (WARNING: destructive!)
	@echo "${COLOR_ERROR}WARNING: This will delete the database!${COLOR_RESET}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		php bin/console doctrine:database:drop --force --if-exists; \
		echo "${COLOR_INFO}Database dropped!${COLOR_RESET}"; \
	else \
		echo "${COLOR_COMMENT}Cancelled.${COLOR_RESET}"; \
	fi

db-reset: ## Reset database with fixtures
	@echo "${COLOR_INFO}Resetting database...${COLOR_RESET}"
	php bin/console doctrine:database:drop --force --if-exists
	php bin/console doctrine:database:create
	@$(MAKE) db-migrate
	@$(MAKE) db-fixtures
	@echo "${COLOR_INFO}Database reset complete!${COLOR_RESET}"

db-migrate: ## Run database migrations
	@echo "${COLOR_INFO}Running migrations...${COLOR_RESET}"
	php bin/console doctrine:migrations:migrate --no-interaction
	@echo "${COLOR_INFO}Migrations complete!${COLOR_RESET}"

db-migration: ## Generate new migration
	@echo "${COLOR_INFO}Generating migration...${COLOR_RESET}"
	php bin/console make:migration
	@echo "${COLOR_INFO}Migration generated! Review it before running db-migrate.${COLOR_RESET}"

db-validate: ## Validate database schema
	@echo "${COLOR_INFO}Validating schema...${COLOR_RESET}"
	php bin/console doctrine:schema:validate

db-diff: ## Show schema differences
	php bin/console doctrine:schema:update --dump-sql

db-fixtures: ## Load database fixtures
	@echo "${COLOR_INFO}Loading fixtures...${COLOR_RESET}"
	php bin/console doctrine:fixtures:load --no-interaction
	@echo "${COLOR_INFO}Fixtures loaded!${COLOR_RESET}"

## —— ✅ Testing ————————————————————————————————————————————————————————————————————
test: ## Run all tests
	@echo "${COLOR_INFO}Running all tests...${COLOR_RESET}"
	php bin/phpunit
	@echo "${COLOR_INFO}Tests complete!${COLOR_RESET}"

test-unit: ## Run unit tests only
	@echo "${COLOR_INFO}Running unit tests...${COLOR_RESET}"
	php bin/phpunit --testsuite=Unit
	@echo "${COLOR_INFO}Unit tests complete!${COLOR_RESET}"

test-functional: ## Run functional/API tests
	@echo "${COLOR_INFO}Running functional tests...${COLOR_RESET}"
	php bin/phpunit --testsuite=Functional
	@echo "${COLOR_INFO}Functional tests complete!${COLOR_RESET}"

test-coverage: ## Generate test coverage report
	@echo "${COLOR_INFO}Generating coverage report...${COLOR_RESET}"
	XDEBUG_MODE=coverage php bin/phpunit --coverage-html var/coverage
	@echo "${COLOR_INFO}Coverage report generated in var/coverage/index.html${COLOR_RESET}"

## —— 🎨 Code Quality ———————————————————————————————————————————————————————————————
lint: ## Run all linters
	@$(MAKE) lint-yaml
	@$(MAKE) lint-twig
	@$(MAKE) lint-doctrine
	@echo "${COLOR_INFO}All linters passed!${COLOR_RESET}"

lint-yaml: ## Validate YAML files
	@echo "${COLOR_INFO}Linting YAML files...${COLOR_RESET}"
	php bin/console lint:yaml config --parse-tags

lint-twig: ## Validate Twig templates
	@echo "${COLOR_INFO}Linting Twig templates...${COLOR_RESET}"
	php bin/console lint:twig templates

lint-doctrine: ## Validate Doctrine schema
	@echo "${COLOR_INFO}Validating Doctrine schema...${COLOR_RESET}"
	php bin/console doctrine:schema:validate --skip-sync

cs-check: ## Check code style (requires PHP-CS-Fixer)
	@echo "${COLOR_INFO}Checking code style...${COLOR_RESET}"
	@if command -v php-cs-fixer > /dev/null; then \
		php-cs-fixer fix --dry-run --diff; \
	else \
		echo "${COLOR_COMMENT}PHP-CS-Fixer not installed. Skipping.${COLOR_RESET}"; \
	fi

cs-fix: ## Fix code style (requires PHP-CS-Fixer)
	@echo "${COLOR_INFO}Fixing code style...${COLOR_RESET}"
	@if command -v php-cs-fixer > /dev/null; then \
		php-cs-fixer fix; \
		echo "${COLOR_INFO}Code style fixed!${COLOR_RESET}"; \
	else \
		echo "${COLOR_COMMENT}PHP-CS-Fixer not installed. Skipping.${COLOR_RESET}"; \
	fi

## —— 🧹 Cache & Cleanup ————————————————————————————————————————————————————————————
cache-clear: ## Clear application cache
	@echo "${COLOR_INFO}Clearing cache...${COLOR_RESET}"
	php bin/console cache:clear
	@echo "${COLOR_INFO}Cache cleared!${COLOR_RESET}"

cache-warmup: ## Warmup application cache
	@echo "${COLOR_INFO}Warming up cache...${COLOR_RESET}"
	php bin/console cache:warmup
	@echo "${COLOR_INFO}Cache warmed up!${COLOR_RESET}"

clean: ## Clean temporary files
	@echo "${COLOR_INFO}Cleaning temporary files...${COLOR_RESET}"
	rm -rf var/cache/* var/log/*
	@echo "${COLOR_INFO}Clean complete!${COLOR_RESET}"

clean-vendor: ## Remove vendor directory
	@echo "${COLOR_COMMENT}Removing vendor directory...${COLOR_RESET}"
	rm -rf vendor/
	@echo "${COLOR_INFO}Vendor removed. Run 'make install' to reinstall.${COLOR_RESET}"

clean-all: clean clean-vendor ## Deep clean (cache + logs + vendor)
	@echo "${COLOR_INFO}Deep clean complete!${COLOR_RESET}"

## —— 🐳 Docker ——————————————————————————————————————————————————————————————————————
docker-up: ## Start Docker containers
	@echo "${COLOR_INFO}Starting Docker containers...${COLOR_RESET}"
	docker-compose up -d
	@echo "${COLOR_INFO}Containers started!${COLOR_RESET}"

docker-down: ## Stop Docker containers
	@echo "${COLOR_INFO}Stopping Docker containers...${COLOR_RESET}"
	docker-compose down
	@echo "${COLOR_INFO}Containers stopped!${COLOR_RESET}"

docker-logs: ## Show Docker container logs
	docker-compose logs -f

## —— 📦 Assets (if using Webpack Encore) ——————————————————————————————————————————
assets-install: ## Install npm dependencies
	@echo "${COLOR_INFO}Installing npm dependencies...${COLOR_RESET}"
	npm install
	@echo "${COLOR_INFO}Done!${COLOR_RESET}"

assets-dev: ## Build assets for development
	@echo "${COLOR_INFO}Building assets for development...${COLOR_RESET}"
	npm run dev
	@echo "${COLOR_INFO}Assets built!${COLOR_RESET}"

assets-watch: ## Watch and rebuild assets
	@echo "${COLOR_INFO}Watching assets...${COLOR_RESET}"
	npm run watch

assets-build: ## Build assets for production
	@echo "${COLOR_INFO}Building assets for production...${COLOR_RESET}"
	npm run build
	@echo "${COLOR_INFO}Production assets built!${COLOR_RESET}"

## —— 🔍 Utilities ———————————————————————————————————————————————————————————————————
make-entity: ## Create new Doctrine entity
	php bin/console make:entity

make-controller: ## Create new controller
	php bin/console make:controller

make-migration-diff: ## Generate migration from schema diff
	php bin/console make:migration

security-check: ## Check for security vulnerabilities
	@if command -v symfony > /dev/null; then \
		symfony security:check; \
	else \
		echo "${COLOR_COMMENT}Symfony CLI not installed. Install it from https://symfony.com/download${COLOR_RESET}"; \
	fi
