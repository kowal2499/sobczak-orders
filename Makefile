SHELL          := /bin/bash
DOCKER_COMPOSE  = docker compose
PHP_CONTAINER   = php-apache
ASSETS_DIR      = app/assets
NVM_INIT        = source ~/.nvm/nvm.sh && cd $(ASSETS_DIR) && nvm use

.PHONY: up down logs dev watch lint check test bash cc pull-db \
        api-token api-tokens api-token-revoke migrations

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
	$(NVM_INIT) && npm run watch

lint:
	$(NVM_INIT) && npm run lint

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

migrations:
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console d:m:m
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console d:m:m --env=test

## Tokeny API (dostęp do API z Postmana/Bruno bez sesji)
# make api-token E=roman@erla.pl NAME="Postman - laptop" [TTL=90]  (TTL=0 -> bezterminowy)
api-token:
	@test -n "$(E)" || { echo "Podaj E=<email>, np. make api-token E=roman@erla.pl NAME=\"Postman\""; exit 1; }
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console app:api-token:create $(E) $(if $(NAME),--name="$(NAME)") $(if $(TTL),--ttl=$(TTL))

# make api-tokens [E=roman@erla.pl]
api-tokens:
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console app:api-token:list $(E)

# make api-token-revoke ID=3
api-token-revoke:
	@test -n "$(ID)" || { echo "Podaj ID=<id> z make api-tokens"; exit 1; }
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console app:api-token:revoke $(ID)

## Baza danych
pull-db:
	app/bin/pull-remote-db
