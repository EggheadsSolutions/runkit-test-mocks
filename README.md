# Eggheads mocks utils

**Запустить контейнеры в фоновом режиме:**
```shell
docker-compose up -d
```

**Перейти в контейнеры:**
```shell
# Для теста php7
docker-compose exec -u www-data php7 bash

# Для теста php8
docker-compose exec -u www-data php8 bash
```
**Запуск тестов внутри контейнера:**
```shell
composer test
```