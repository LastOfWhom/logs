.DEFAULT_GOAL = all

-include .env

DOCKER_COMPOSE = docker compose
PHP            = $(DOCKER_COMPOSE) exec app php

# Окружение приложения
export APP_ENV    ?= dev
export APP_SECRET ?= changeme32charsecretkey00000000
export APP_DEBUG  ?= 1

# RabbitMQ
export RABBITMQ_USER              ?= guest
export RABBITMQ_PASSWORD          ?= guest
export RABBITMQ_VHOST             ?= /
export RABBITMQ_PORT              ?= 5672
export RABBITMQ_MANAGEMENT_PORT   ?= 15672

# Nginx
export NGINX_PORT ?= 8081

all:
	@echo "Log Service"
	@echo ""
	@echo "\033[1mНастройка\033[0m"
	@printf '    %-10s %s\n' "env"   "-- Создать .env из переменных по умолчанию"
	@echo ""
	@echo "\033[1mЖизненный цикл\033[0m"
	@printf '    %-10s %s\n' "build"   "-- Собрать образы и запустить контейнеры"
	@printf '    %-10s %s\n' "rebuild" "-- Пересобрать образы без кеша"
	@printf '    %-10s %s\n' "start"   "-- Запустить контейнеры"
	@printf '    %-10s %s\n' "stop"    "-- Остановить контейнеры (данные сохраняются)"
	@printf '    %-10s %s\n' "down"    "-- Удалить контейнеры и сети"
	@echo ""
	@echo "\033[1mРазработка\033[0m"
	@printf '    %-10s %s\n' "composer" "-- Установить зависимости composer"
	@printf '    %-10s %s\n' "shell"    "-- Открыть консоль внутри контейнера app"
	@printf '    %-10s %s\n' "test"     "-- Запустить тесты"

env:
	@echo "# Окружение приложения"                                   > .env
	@echo "APP_ENV=$(APP_ENV)"                                       >> .env
	@echo "APP_SECRET=$(APP_SECRET)"                                 >> .env
	@echo "APP_DEBUG=$(APP_DEBUG)"                                   >> .env
	@echo ""                                                         >> .env
	@echo "# Symfony Messenger / RabbitMQ"                          >> .env
	@echo "# Формат: amqp://user:password@host:port/vhost"          >> .env
	@echo "MESSENGER_TRANSPORT_DSN=$(MESSENGER_TRANSPORT_DSN)"       >> .env
	@echo ""                                                         >> .env
	@echo "# Параметры RabbitMQ (используются в docker-compose)"    >> .env
	@echo "RABBITMQ_USER=$(RABBITMQ_USER)"                           >> .env
	@echo "RABBITMQ_PASSWORD=$(RABBITMQ_PASSWORD)"                   >> .env
	@echo "RABBITMQ_VHOST=$(RABBITMQ_VHOST)"                        >> .env
	@echo "RABBITMQ_PORT=$(RABBITMQ_PORT)"                           >> .env
	@echo "RABBITMQ_MANAGEMENT_PORT=$(RABBITMQ_MANAGEMENT_PORT)"     >> .env
	@echo ""                                                         >> .env
	@echo "# Nginx"                                                  >> .env
	@echo "NGINX_PORT=$(NGINX_PORT)"                                 >> .env

build:
	$(DOCKER_COMPOSE) up -d --build

rebuild:
	$(DOCKER_COMPOSE) build --no-cache
	$(DOCKER_COMPOSE) up -d

start:
	$(DOCKER_COMPOSE) up -d

stop:
	$(DOCKER_COMPOSE) stop

down:
	$(DOCKER_COMPOSE) down

composer:
	$(DOCKER_COMPOSE) exec app composer install --no-interaction --prefer-dist

shell:
	$(DOCKER_COMPOSE) exec app sh

test:
	$(PHP) vendor/bin/phpunit --testdox
