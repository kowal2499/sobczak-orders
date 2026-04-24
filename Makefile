DOCKER_COMPOSE = docker compose
PHP_CONTAINER  = php-apache
ASSETS_DIR     = app/assets

.PHONY: up down logs dev watch lint check test bash cc pull-db

## Docker
up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

logs:
	$(DOCKER_COMPOSE) logs -f

## Dev (kontenery + watcher)
dev: up watch

## Frontend
watch:
	cd $(ASSETS_DIR) && npm run watch

lint:
	cd $(ASSETS_DIR) && npm run lint

## PHP / Backend
check:
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) composer check

test:
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php vendor/bin/phpunit $(F)

bash:
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) bash

## Symfony
cc:
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console cache:clear
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console cache:clear --env=test

## Baza danych
pull-db:
	app/bin/pull-remote-db
