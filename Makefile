.PHONY: build start stop down shell test

DOCKER_COMPOSE = docker compose
PHP            = $(DOCKER_COMPOSE) exec app php

build:
	$(DOCKER_COMPOSE) build --no-cache
	$(DOCKER_COMPOSE) up -d
	$(DOCKER_COMPOSE) exec app sh -c 'until [ -f vendor/autoload.php ]; do sleep 1; done'

start:
	$(DOCKER_COMPOSE) up -d

stop:
	$(DOCKER_COMPOSE) stop

down:
	$(DOCKER_COMPOSE) down

shell:
	$(DOCKER_COMPOSE) exec app sh

test:
	$(PHP) vendor/bin/phpunit --testdox