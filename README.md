# log-service

Микросервис для сбора, валидации и асинхронной публикации логов от агентов в RabbitMQ через Symfony Messenger.


## Быстрый старт

```bash
# 1. Клонировать репозиторий
git clone git@github.com:LastOfWhom/logs.git

# 2. Создать .env
make env

# 3. Собрать образы и запустить контейнеры
make build
```

`make build` делает всё сам:
- собирает Docker-образ с PHP 8.3 и `ext-amqp`
- поднимает контейнеры (app, nginx, rabbitmq)
- entrypoint автоматически запускает `composer install`

После запуска:
- API: `http://localhost:8081`
- RabbitMQ Management UI: `http://localhost:15672` (guest / guest)

---

## Команды

```bash
make env    # Создать .env из переменных по умолчанию
make build  # Собрать образы и запустить (первый запуск)
make start  # Запустить контейнеры
make stop   # Остановить контейнеры (данные сохраняются)
make down   # Удалить контейнеры и сети
make shell  # Открыть консоль внутри контейнера app
make test   # Запустить тесты
```

---

## API

### POST /api/logs/ingest

Принимает батч логов, валидирует и публикует каждую запись в RabbitMQ.

**Обязательные поля каждой записи:**

| Поле        | Тип    | Описание                                                              |
|-------------|--------|-----------------------------------------------------------------------|
| `timestamp` | string | ISO-8601 дата и время                                                 |
| `level`     | string | `emergency` `alert` `critical` `error` `warning` `notice` `info` `debug` |
| `service`   | string | Имя сервиса-источника                                                 |
| `message`   | string | Текст записи                                                          |

**Опциональные поля:** `context` (object), `trace_id` (string)

**Лимит:** не более 1000 записей в одном запросе.

---

### 202 Accepted — успешный запрос

```bash
curl -s -X POST http://localhost:8081/api/logs/ingest \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [
      {
        "timestamp": "2026-02-26T10:30:45Z",
        "level": "error",
        "service": "auth-service",
        "message": "User authentication failed",
        "context": {"user_id": 123, "ip": "192.168.1.1"},
        "trace_id": "abc123def456"
      },
      {
        "timestamp": "2026-02-26T10:30:46Z",
        "level": "info",
        "service": "api-gateway",
        "message": "Request processed",
        "context": {"endpoint": "/api/users", "response_time_ms": 145}
      }
    ]
  }' | jq .
```

```json
{
  "status": "accepted",
  "batch_id": "batch_550e8400e29b41d4a716446655440000",
  "logs_count": 2
}
```

### 400 — ошибка валидации

```bash
curl -s -X POST http://localhost:8081/api/logs/ingest \
  -H "Content-Type: application/json" \
  -d '{"logs": [{"level": "info", "service": "svc"}]}' | jq .
```

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "logs[0].timestamp": ["Field \"timestamp\" is required and must not be empty"],
    "logs[0].message":   ["Field \"message\" is required and must not be empty"]
  }
}
```

---

## Тесты

```bash
make test
```

Integration-тесты используют `in-memory` транспорт и не требуют запущенного RabbitMQ.
